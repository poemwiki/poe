<?php

namespace App\Console\Poem;

use App\Models\Author;
use App\Models\Crawl;
use App\Models\Poem;
use App\Rules\NoDuplicatedPoem;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class importAllPoetry extends Command {
    protected $signature = 'poem:importAllPoetry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'craw author & poem from allpoetry';

    public static $source   = 'allpoetry';
    public static $causerID = 2;

    public function __construct() {
        parent::__construct();

        // activity log need a causer, so we have to log in as self::$causerID first
        $causer = User::find(self::$causerID);
        Auth::login($causer);
        // dd(date_default_timezone_get()); // UTC
    }

    /**
     * Execute the console command.
     * @return int
     */
    public function handle() {
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
                $author[$to] = ['en' => self::clean($poetCrawl->result[$from])];
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
                    'upload_user_id' => self::$causerID,
                    'poet_id'        => $insertedAuthor->id,
                    'original_id'    => $poemCrawl->id,
                    'from'           => strlen($poemCrawl->url) > 255 ? self::$source : $poemCrawl->url,
                    // 'created_at'     => now(),
                    // 'updated_at'     => now(),
                ];

                $exportFields = $poemCrawl->export_setting['fields'];
                foreach ($exportFields as $from => $to) {
                    $poem[$to] = self::clean($poemCrawl->result[$from]);
                }

                // dd($poem);
                try {
                    $validator = Validator::make($poem, [
                        'title'   => 'required|string|max:255',
                        'poem'    => [new NoDuplicatedPoem(null), 'required', 'string', 'min:10', 'max:65500'],
                        'poet_id' => 'integer|exists:' . \App\Models\Author::class . ',id',
                        'from'    => 'required|string|max:255',
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

    public static function clean($str) {
        if (gettype($str) !== 'string') {
            return $str;
        }

        $str = preg_replace('~\xc2\xa0~', ' ', $str);
        $str = str_replace("\r\n", "\n", $str);
        $str = preg_replace('/(?<=[^\n])\n\n(?=[^\n])/', "\n", $str);
        $str = preg_replace('/\n\n\n+/', "\n\n", $str);

        return trim($str);
    }
}
