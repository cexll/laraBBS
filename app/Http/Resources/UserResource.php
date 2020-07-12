<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @var bool
     */
    protected $showSensitiveFields = false;


    /**
     * Transform the resource into an array.
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        if (!$this->showSensitiveFields) {
            $this->resource->addHidden(['phone', 'email']);
        }

        $data = parent::toArray($request);

        $data['bound_phone'] = $this->resource->phone ? true : false;
        $data['bound_wechat'] = ($this->resource->weixin_unionid || $this->resource->weixin_openid) ? true : false;

        return $data;
    }

    /**
     * @return UserResource
     */
    public function showSensitiveFields(): UserResource
    {
        $this->showSensitiveFields = true;

        return $this;
    }
}
