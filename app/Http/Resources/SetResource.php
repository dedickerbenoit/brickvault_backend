<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'set_num' => $this->set_num,
            'name' => $this->name,
            'theme' => $this->whenLoaded('theme', fn () => [
                'id' => $this->theme->id,
                'name' => $this->theme->name,
            ]),
            'year' => $this->year,
            'num_parts' => $this->num_parts,
            'img_url' => $this->img_url,
        ];
    }
}
