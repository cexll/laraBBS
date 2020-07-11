<?php

namespace App\Http\Controllers\Api;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Http\Requests\Api\VerificationCodeRequest;
use Illuminate\Http\Request;
use Overtrue\EasySms\EasySms;

class VerificationCodesController extends Controller
{
    public function store(VerificationCodeRequest $request, EasySms $easySms)
    {
        AlibabaCloud::accessKeyClient(config('easysms.gateways.aliyun.access_key_id'), config('easysms.gateways.aliyun.access_key_secret'))->regionId('cn-hangzhou')->asDefaultClient();

        $phone = $request->phone;

        if (!app()->environment('production')) {
            $code = '1234';
        } else {
            // 生成4位随机数，左侧补0
            $code = str_pad(random_int(1, 9999), 4, 0, STR_PAD_LEFT);

            try {
                $result = AlibabaCloud::rpc()
                    ->product('Dysmsapi')
                    // ->scheme('https') // https | http
                    ->version(date('Y-m-d'))
                    ->action('SendSms')
                    ->method('POST')
                    ->host('dysmsapi.aliyuncs.com')
                    ->options([
                        'query' => [
                            'RegionId' => "cn-hangzhou",
                            'PhoneNumbers' => $phone,
                            'SignName' => config('easysms.gateways.aliyun.sign_name'),
                            'TemplateCode' => config('easysms.gateways.aliyun.templates.register'),
                            'TemplateParam' => "{\"code\":\"$code\"}",
                        ],
                    ])
                    ->request();
//                print_r($result->toArray());
            } catch (ClientException $e) {
                abort(500, $e->getErrorMessage());
            } catch (ServerException $e) {
                abort(500, $e->getErrorMessage());
            }
        }


        $key = 'verificationCode_'.Str::random(15);
        $expiredAt = now()->addMinutes(5);
        // 缓存验证码 5 分钟过期。
        Cache::put($key, ['phone' => $phone, 'code' => $code], $expiredAt);

        return response()->json([
            'key' => $key,
            'expired_at' => $expiredAt->toDateTimeString(),
        ])->setStatusCode(201);
    }
}


