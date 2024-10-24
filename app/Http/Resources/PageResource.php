<?php

namespace App\Http\Resources;
use App\Models\FaqCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class PageResource extends JsonResource
{


    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->{'name_' . app()->getLocale()},
             'content' => $this->{'content_' . app()->getLocale()},
            'template' => $this->template,
        ];
        if ($this->template == 'faq') {
            $data['category'] =  FaqCategory::orderBy('sort')->get()->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->{'name_' . app()->getLocale()},
                    'questions' => $category->questions->map(function ($question) {
                        return [
                            'id' => $question->id,
                            'question' => $question->{'title_' . app()->getLocale()},
                            'answer' => $question->{'description_' . app()->getLocale()},
                        ];
                    }),
                ];
            });
        }

        return $data;
    }


}
