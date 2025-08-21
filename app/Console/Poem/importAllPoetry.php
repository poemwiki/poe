<?php

namespace App\Console\Poem;

use App\Models\Author;
use App\Models\Crawl;
use App\Models\Poem;
use App\Rules\NoDuplicatedPoem;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class importAllPoetry extends Command {
    protected $signature = 'poem:importAllPoetry';

    /**
     * 导入要求：
     * 1. 通过 NoDuplicatedPoem 等 validator 验证
     * 2. 保持 activity_log 完整
     * 3. 保证导入条目的 created_at 和 updated_at 为导入时的时间，避开时区 bug.
     * 4. 每首诗的 poet_id 对应到相关 author 的 id
     * 5. 将来源 URL 填入 poem.from 字段.
     */
    protected $description = 'craw author & poem from allpoetry';

    public static $source   = 'allpoetry';
    public static $causerID = 2; // uploader user id

    public function __construct() {
        parent::__construct();

        if (!App::runningInConsole()) {
            Log::error('This command can only be used in console mode.');

            return;
        }

        // activity log need a causer, so we have to log in as self::$causerID first
        $causer = User::find(self::$causerID);
        if ($causer) {
            Auth::login($causer);
        } else {
            // In test / fresh DB this user may not exist; skip login to prevent null auth errors.
        }
        // dd(date_default_timezone_get()); // UTC
    }

    /**
     * Execute the console command.
     * @return int
     */
    public function handle(): int {
        // dd(date_default_timezone_get()); // @TODO Why the timezone changed to PRC?
        $poetCrawls = Crawl::select(['id', 'name', 'export_setting', 'result'])->where([
            'source' => self::$source,
            'model'  => 'App\Models\Author'
        ])->whereNull('exported_id')->get();

        // have to set timezone here, otherwise the timezone will be wrong
        date_default_timezone_set(config('app.timezone', 'UTC'));
        $failedPoemCrawl = [];
        foreach ($poetCrawls as $poetCrawl) {
            $exportFields = $poetCrawl->export_setting['fields'];
            $author       = ['upload_user_id' => self::$causerID];
            foreach ($exportFields as $from => $to) {
                $author[$to] = ['en' => textClean($poetCrawl->result[$from], 0)];
            }

            $insertedAuthor         = Author::create($author);
            $poetCrawl->exported_id = $insertedAuthor->id;
            $poetCrawl->save();

            $poemCrawls = Crawl::select(['id', 'name', 'export_setting', 'result', 'url'])->where([
                'source'     => self::$source,
                'model'      => 'App\Models\Poem',
                'f_crawl_id' => $poetCrawl->id,
            ])->whereNull('exported_id')->get();

            foreach ($poemCrawls as $poemCrawl) {
                $poem = [
                    'upload_user_id'              => self::$causerID,
                    'poet_id'                     => $insertedAuthor->id,
                    'original_id'                 => null,
                    'language_id'                 => 2,
                    'from'                        => strlen($poemCrawl->url) > 255 ? self::$source : $poemCrawl->url,
                    'is_owner_uploaded'           => Poem::$OWNER['none'],
                    'poet'                        => $insertedAuthor->name_lang,
                    // 'created_at'     => now(),
                    // 'updated_at'     => now(),
                ];

                $exportFields = $poemCrawl->export_setting['fields'];
                foreach ($exportFields as $from => $to) {
                    $poem[$to] = textClean($poemCrawl->result[$from]);
                }

                // dd($poem);
                try {
                    // TODO move this to a public custom validator
                    $validator = Validator::make($poem, [
                        'title'                  => 'required|string|max:255',
                        'poet'                   => 'required|string|max:255',
                        'poem'                   => [new NoDuplicatedPoem(null), 'required', 'string', 'min:10', 'max:65500'],
                        'poet_id'                => 'integer|exists:' . \App\Models\Author::class . ',id',
                        'is_owner_uploaded'      => ['required', Rule::in([Poem::$OWNER['none'], Poem::$OWNER['uploader'], Poem::$OWNER['translatorUploader']])],
                        'from'                   => 'nullable|string|max:255',
                    ]);
                    $validator->validate();
                } catch (ValidationException $e) {
                    $failedRules = $e->validator->failed();
                    if (isset($failedRules['poem']['App\Rules\NoDuplicatedPoem'])) {
                        logger()->info('poemCrawl [' . $poemCrawl->id . '] duplicated with existed poem: ', $e->errors());
                        $poemCrawl->exported_id = 0;
                        $poemCrawl->save();
                    } else {
                        logger()->info('poemCrawl [' . $poemCrawl->id . '] failed to validate: ', $e->errors());
                    }
                    $failedPoemCrawl[] = $poem;

                    continue;
                }

                $insertedPoem = Poem::create($poem);

                $poemCrawl->exported_id = $insertedPoem->id;
                $poemCrawl->save();
            }
        }

        logger()->info('failedPoemCrawl: ', $failedPoemCrawl);

        return 0;
    }
}
