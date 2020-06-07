<?php

namespace App\Console\Commands;

use hmphu\deathbycaptcha\DeathByCaptchaAccessDeniedException;
use Illuminate\Console\Command;
use Curl\Curl;
use hmphu\deathbycaptcha\DeathByCaptchaSocketClient;
use hmphu\deathbycaptcha\DeathByCaptchaClient;

class Repair extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:repair {--imei=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $img_captcha = '/tmp/myfile.jpg';

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
        $url = 'http://repair.salt.ch/CaseWizard/Wizard/27427839-ade1-46ad-ab14-e03bcdf4b5b4/Salt/RepairAtHome';
        $this->curl->setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/83.0.4103.61 Chrome/83.0.4103.61 Safari/537.36');
        $this->curl->setOpt(CURLOPT_SSL_VERIFYHOST, 0);
        $this->curl->setOpt(CURLOPT_FOLLOWLOCATION, 1);
        $this->curl->setOpt(CURLOPT_COOKIEJAR, 'cookie.txt');
        $this->curl->get($url);
        $this->curl->setReferer('http://repair.salt.ch/CaseWizard/Wizard/27427839-ade1-46ad-ab14-e03bcdf4b5b4/Salt/RepairAtHome');
        $this->curl->post('http://repair.salt.ch/CaseWizard/StepInsuranceCheckRepairAtHome/EditStep',
            ['SelectedAnswers[0]' => '0', 'ContractNumber' => '123456'], true);
        $this->curl->post('http://repair.salt.ch/CaseWizard/StepSaltSerialNumberProduct/RenderStep',
            [], true);
        $this->curl->setHeader('Connection', 'keep-alive');
        $this->curl->setHeader('Accept', 'image/webp,image/apng,image/*,*/*;q=0.8');
        $this->curl->get('http://repair.salt.ch/Account/Captcha');
        file_put_contents($this->img_captcha, $this->curl->response);
        $text = $this->captcha($this->img_captcha);
        unlink($this->img_captcha);
        $this->curl->setHeader('X-Requested-With', 'XMLHttpRequest');
        $this->curl->setHeader('Content-Type', 'application/json');
        $this->curl->setHeader('Accept', 'application/json, text/javascript, */*; q=0.01');
        $this->curl->get('http://repair.salt.ch/CaseWizard/StepSaltSerialNumberProduct/ValidateCaptcha?captcha=' . $text);

        $this->curl->get('http://repair.salt.ch/CaseWizard/StepSaltSerialNumberProduct/FmiPInfos?imei='.$this->imei.'&productId=1892441&_=');

        var_dump($this->curl->response);


    }

    protected function captcha($img)
    {

        $deathByCaptchaUser = env('CAPTCHA_LOGIN');
        $deathByCaptchaPassword = env('CAPTCHA_PASSWORD');
        $client = new DeathByCaptchaSocketClient($deathByCaptchaUser, $deathByCaptchaPassword);

        try {
            $balance = $client->get_balance();
            if ($balance > 0) {
                /* Put your CAPTCHA file name or opened file handler, and optional solving timeout (in seconds) here: */
                $captcha = $client->decode($img, DeathByCaptchaClient::DEFAULT_TIMEOUT * 2);
                if ($captcha) {
                    $text = $captcha['text'];
                }
            }
        } catch (DeathByCaptchaAccessDeniedException $e) {
            dd($e->getMessage());
        }

        return $text;
    }


}
