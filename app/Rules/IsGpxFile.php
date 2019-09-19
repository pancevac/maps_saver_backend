<?php


namespace App\Rules;


use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;

class IsGpxFile implements Rule
{

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($value instanceof UploadedFile && $value->getClientOriginalExtension() == 'gpx') {
            return true;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string|array
     */
    public function message()
    {
        return 'Unsupported file extension. Extension must be gpx!';
    }
}