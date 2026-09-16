<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreKeywordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'keyword' => ['required', 'string', 'max:160'],
            'target_url' => ['nullable', 'url', 'max:255'],
            'country' => ['required', 'string', 'max:80'],
            'device' => ['required', 'in:desktop,mobile'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $project = $this->route('project');
                $limit = $this->user()->planLimit('keywords_per_project');

                if ($project && $project->keywords()->count() >= $limit) {
                    $validator->errors()->add('plan', 'Your '.ucfirst($this->user()->plan)." plan supports {$limit} keywords per website.");
                }
            },
        ];
    }
}
