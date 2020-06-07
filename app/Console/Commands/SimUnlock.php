<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Curl\Curl;
use KubAT\PhpSimple\HtmlDomParser;

class SimUnlock extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:unlock {--imei=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $imei, $curl;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->curl = new Curl();
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->imei = (int)$this->option('imei');
        $url = 'http://sim-unlock.net/imei_chk/' . $this->imei;

        $this->curl->get($url);
        if ($this->curl->error) {
            echo 'Error: ' . $this->curl->errorCode . ': ' . $this->curl->errorMessage . "\n";
        } else {
            $dom = HtmlDomParser::str_get_html($this->curl->response);
        }
        $model_h4 = $dom->find('h4', 0)->text();
        $model = trim(str_replace('We have found your mobile as', '', $model_h4));
        if (!(strlen($model) > 5)) {
            $model = $dom->find('center > a', 0)->text();
        }

        var_dump($model);
    }
}
