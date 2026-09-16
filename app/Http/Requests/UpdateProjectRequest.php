<?php

namespace App\Http\Requests;

use App\Services\CrawlUrlValidator;
use App\Services\ProjectUrlNormalizer;
use Illuminate\Database\Query\Builder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('url')) {
            return;
        }

        try {
            $this->merge(['url' => app(ProjectUrlNormalizer::class)->normalize((string) $this->input('url'))]);
        } catch (InvalidArgumentException) {
            //
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'url' => [
                'required',
                'string',
                'max:255',
                Rule::unique('projects', 'url')
                    ->where(fn (Builder $query) => $query->where('user_id', $this->user()->id))
                    ->ignore($this->route('project')),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    try {
                        $url = app(ProjectUrlNormalizer::class)->normalize((string) $value);
                    } catch (InvalidArgumentException $e) {
                        $fail($e->getMessage());

                        return;
                    }

                    if (! app(CrawlUrlValidator::class)->isSafe($url)) {
                        $fail('Enter a public HTTP or HTTPS website URL.');
                    }
                },
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'url.unique' => 'This website is already being monitored.',
        ];
    }
}
