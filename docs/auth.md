**Web 登录（邮箱 + 密码）**
- 目标：梳理仅“邮箱 + 密码”的 Web 登录流程；不包含 API/第三方登录，也不展开验证码细节（保留原样，供参考）。

**入口路由（Web）**
```
// routes/web.php（节选）
Auth::routes(['verify' => true]);

if (User::isWechat()) {
    // 此处使用 Route::any, 因为微信服务端认证的时候是 GET, 接收用户消息时是 POST
    Route::any('/login', [\App\Http\Controllers\Auth\LoginWechatController::class, 'login'])
        ->name('login')->middleware(['web', 'wechat.oauth:default,snsapi_userinfo']);
} elseif (User::isWeApp()) {
    Route::any('/login', [\App\Http\Controllers\API\LoginWeAppController::class, 'login'])
        ->name('login');
} else {
    Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])
        ->name('login');
}
```
- 含义：普通浏览器环境命中最后一个分支，展示登录页面，走邮箱+密码的标准表单登录流程。

**控制器与关键逻辑（Web 登录）**
```
// app/Http/Controllers/Auth/LoginController.php（节选）
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller {
    /*
    | Login Controller
    | 处理 Web 登录与重定向
    */

    use AuthenticatesUsers;

    /** 登录成功后的重定向路径 */
    protected $redirectTo = RouteServiceProvider::RANDOM_POEM;

    public function __construct() {
        $this->middleware('guest')->except('logout');
        if (request()->get('ref')) {
            $this->redirectTo = request()->get('ref');
        }
    }

    /**
     * 请求校验：邮箱 + 密码（以及验证码，本文不展开）
     */
    protected function validateLogin(Request $request) {
        $request->validate([
            $this->username() => 'required|string',
            'password'        => 'required|string',
            'captcha'         => 'required|captcha'
        ]);
    }
}
```
- 说明：
  - 认证具体流程（验证凭证、创建会话、失败处理）由 `AuthenticatesUsers` Trait 提供。
  - 未覆写 `username()`，默认用户名字段为 `email`。表单需提交 `email` 与 `password`。
  - 登录成功后重定向到 `RANDOM_POEM`，若存在 `?ref=/path` 则按该路径重定向。

**重定向常量位置**
```
// app/Providers/RouteServiceProvider.php（节选）
class RouteServiceProvider extends ServiceProvider {
    public const RANDOM_POEM     = '/poems/random';
    // public const HOME = '/'; 等其他常量...
}
```

**鉴权与会话配置（仅 Web）**
```
// config/auth.php（节选，仅与 Web 会话相关）
return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\User::class,
        ],
    ],
];
```
- 含义：Web 登录使用 `web` guard 与 `users` provider；会话通过 `session` 驱动维持登录状态。

**密码哈希出现场景（用于理解登录校验）**
```
// app/Http/Controllers/Auth/RegisterController.php（节选）
use Illuminate\Support\Facades\Hash;

protected function create(array $data) {
    return User::create([
        // ...
        'password' => Hash::make($data['password']),
    ]);
}
```
- 说明：注册/修改密码时进行哈希；登录时 `AuthenticatesUsers` 会将明文密码与存储的哈希进行比对。

**典型交互（Web，邮箱 + 密码）**
- GET `/login` 展示登录页；POST `/login` 提交表单。
- 表单字段：`email`（字符串，必填）、`password`（字符串，必填）。项目当前还校验 `captcha`（验证码），若业务不需要可在控制器与表单层移除该规则。
- 结果：
  - 成功：写入会话并重定向至 `/poems/random` 或 `?ref=` 指定地址。
  - 失败：返回登录页并显示校验/认证错误。

**跨框架复刻要点**
- 路由：提供 `GET /login`（展示表单）与 `POST /login`（提交认证），支持 `ref` 重定向参数。
- 校验：校验 `email` 与 `password`；验证码按需可选。
- 认证：按 `email` 查用户，验证密码哈希，成功后建立会话或等价登录状态。
- 重定向：默认 `/poems/random`，支持 `ref` 覆盖。

注：本文档仅覆盖 Web 侧“邮箱 + 密码”登录；API 登录与第三方（微信/小程序）不在范围内。

**TypeScript/Next.js 等价哈希（与 Laravel bcrypt 兼容）**
- Laravel 使用 bcrypt 作为默认哈希算法：参见 `config/hashing.php` 中 `driver = 'bcrypt'`，轮数（成本因子）默认为 `BCRYPT_ROUNDS` 环境变量或 10。
- 为了在 Next.js（Node.js 运行时）中生成/校验与 Laravel 完全兼容的密码哈希，建议使用 `bcrypt`（原生模块）或 `bcryptjs`（纯 JS）。二者默认都能处理 PHP Laravel 产生的 `$2y$` 前缀；若有个别版本不兼容，可将 `$2y$` 替换为 `$2b$` 再校验。

```
// utils/password.ts（Node.js 运行时）
import bcrypt from 'bcrypt'; // 或者：import bcrypt from 'bcryptjs';

// 与 Laravel 配置对齐：默认 10 轮，可通过环境变量覆盖
const ROUNDS = Number(process.env.BCRYPT_ROUNDS ?? 10);

export async function hashPassword(plain: string): Promise<string> {
  const salt = await bcrypt.genSalt(ROUNDS);
  return bcrypt.hash(plain, salt);
}

export async function verifyPassword(plain: string, hash: string): Promise<boolean> {
  // 某些库版本如不识别 `$2y$`，可切换前缀再校验：
  const normalized = hash.startsWith('$2y$') ? hash.replace('$2y$', '$2b$') : hash;
  return bcrypt.compare(plain, normalized);
}
```

```
// app/api/login/route.ts（Next.js 13+ Route Handler 示例，基于 Node.js 运行时）
import { NextRequest, NextResponse } from 'next/server';
import { verifyPassword } from '@/utils/password';
import { getUserByEmail } from '@/data/users'; // 需实现：返回包含 passwordHash 的用户

export const runtime = 'nodejs'; // 使用 Node 运行时以便使用 bcrypt 原生模块

export async function POST(req: NextRequest) {
  const { email, password } = await req.json();
  if (!email || !password) {
    return NextResponse.json({ message: 'Invalid payload' }, { status: 400 });
  }

  const user = await getUserByEmail(email);
  if (!user) {
    return NextResponse.json({ message: 'Invalid credentials' }, { status: 401 });
  }

  const ok = await verifyPassword(password, user.passwordHash);
  if (!ok) {
    return NextResponse.json({ message: 'Invalid credentials' }, { status: 401 });
  }

  // 这里根据你的系统：创建会话/Set-Cookie 或签发 JWT 等
  return NextResponse.json({ id: user.id, email: user.email });
}
```

注意事项：
- 运行时选择：Next.js Edge Runtime 无法使用 Node 原生模块；如需 Edge，请改用 `bcryptjs`（纯 JS）或迁移到兼容的方案，但为与 Laravel 完全兼容，建议在 Node 运行时使用 `bcrypt`/`bcryptjs`。
- 轮数一致性：确保 `ROUNDS` 与 Laravel 的 `BCRYPT_ROUNDS` 一致（默认 10），否则哈希成本差异会影响性能但不影响校验；兼容性校验不依赖相同轮数，只需保留数据库中的哈希原值。
- `$2y$`/`$2b$` 前缀：Laravel 生成 `$2y$`；大多数 JS 库可直接校验。若出现不兼容，按示例进行前缀标准化再比较。
