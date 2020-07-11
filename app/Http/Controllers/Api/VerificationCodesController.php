<?php

namespace App\Http\Controllers\Api;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Http\Requests\Api\VerificationCodeRequest;
use Illuminate\Http\Request;
use Overtrue\EasySms\EasySms;

class VerificationCodesController extends Controller
{
    public function store(VerificationCodeRequest $request, EasySms $easySms)
    {
        $captchaData = Cache::get($request->captcha_key);

        if (!$captchaData) {
            abort(403, '图片验证码已失效');
        }

        if (!hash_equals($captchaData['code'], $request->captcha_code)) {
            // 验证错误就清除缓存
            Cache::forget($request->captcha_key);
            throw new AuthenticationException('验证码错误');
        }

        $phone = $request->phone;

        AlibabaCloud::accessKeyClient(config('easysms.gateways.aliyun.access_key_id'), config('easysms.gateways.aliyun.access_key_secret'))->regionId('cn-hangzhou')->asDefaultClient();

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
                abort(500, $e->getErrorMessage() ?: '短信发送异常');
            } catch (ServerException $e) {
                abort(500, $e->getErrorMessage() ?: '短信发送异常');
            }
        }


        $key = 'verificationCode_'.Str::random(15);
        $expiredAt = now()->addMinutes(5);
        // 缓存验证码 5 分钟过期。
        Cache::put($key, ['phone' => $phone, 'code' => $code], $expiredAt);
        // 清楚图片验证码缓存
        Cache::forget($request->captcha_key);


        return response()->json([
            'key' => $key,
            'expired_at' => $expiredAt->toDateTimeString(),
        ])->setStatusCode(201);
    }
}


