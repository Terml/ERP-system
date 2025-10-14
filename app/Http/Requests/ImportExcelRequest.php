<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportExcelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAnyRole(['admin', 'manager']);
    }
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
            'options' => 'nullable|array',
            'options.overwrite_existing' => 'boolean',
            'options.skip_duplicates' => 'boolean',
            'options.validate_only' => 'boolean',
            'options.chunk_size' => 'nullable|integer|min:100|max:10000',
        ];
    }
    public function messages(): array
    {
        return [
            'file.required' => 'Файл для импорта обязателен',
            'file.file' => 'Загруженный файл не является валидным файлом',
            'file.mimes' => 'Файл должен быть в формате Excel (.xlsx, .xls) или CSV',
            'file.max' => 'Размер файла не должен превышать 10MB',
            'options.array' => 'Опции должны быть переданы в виде массива',
            'options.overwrite_existing.boolean' => 'Опция перезаписи должна быть булевым значением',
            'options.skip_duplicates.boolean' => 'Опция пропуска дубликатов должна быть булевым значением',
            'options.validate_only.boolean' => 'Опция только валидации должна быть булевым значением',
            'options.chunk_size.integer' => 'Размер чанка должен быть целым числом',
            'options.chunk_size.min' => 'Размер чанка должен быть не менее 100',
            'options.chunk_size.max' => 'Размер чанка должен быть не более 10000',
        ];
    }
    public function prepareForValidation(): void
    {
        $options = $this->input('options', []);
        $this->merge([
            'options' => array_merge([
                'overwrite_existing' => false,
                'skip_duplicates' => true,
                'validate_only' => false,
                'chunk_size' => 1000,
            ], $options),
        ]);
    }
    public function getFileOptions(): array
    {
        return $this->input('options', []);
    }
}
