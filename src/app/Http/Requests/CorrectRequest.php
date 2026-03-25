<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Carbon\Carbon;

class CorrectRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'punch_in' => 'nullable|date_format:H:i',
            'punch_out' => 'nullable|date_format:H:i',
            'note' => 'required|string|max:1000',
            'breaks' => 'nullable|array',
            'breaks.*.start' => 'nullable|date_format:H:i',
            'breaks.*.end' => 'nullable|date_format:H:i',
        ];
    }

    public function messages()
    {
        return [
            'note.required' => '備考を記入してください',
        ];
    }

    /**
     * Redirect back to attendance detail on validation fail when id param exists
     */
    public function getRedirectUrl()
    {
        $id = $this->route('id') ?? $this->input('id');
        if ($id) {
            try {
                return route('attendance.detail', $id);
            } catch (\Exception $e) {
                // fallback to parent
            }
        }
        return parent::getRedirectUrl();
    }

    protected function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $punchIn = $this->input('punch_in');
            $punchOut = $this->input('punch_out');

            // 1. 出勤/退勤の整合性
            if ($punchIn && $punchOut) {
                try {
                    $in = Carbon::createFromFormat('H:i', $punchIn);
                    $out = Carbon::createFromFormat('H:i', $punchOut);
                    if ($in->gt($out)) {
                        $validator->errors()->add('punch_in', '出勤時間もしくは退勤時間が不適切な値です');
                        $validator->errors()->add('punch_out', '出勤時間もしくは退勤時間が不適切な値です');
                    }
                } catch (\Exception $e) {
                    // format errors handled by rules
                }
            }

            // 2 & 3. 休憩の整合性チェック
            $breaks = $this->input('breaks', []);
            if (is_array($breaks)) {
                foreach ($breaks as $i => $b) {
                    $start = $b['start'] ?? null;
                    $end = $b['end'] ?? null;

                    try {
                        $startC = $start ? Carbon::createFromFormat('H:i', $start) : null;
                    } catch (\Exception $e) { $startC = null; }

                    try {
                        $endC = $end ? Carbon::createFromFormat('H:i', $end) : null;
                    } catch (\Exception $e) { $endC = null; }

                    if ($startC) {
                        if ($punchIn) {
                            try {
                                $in = Carbon::createFromFormat('H:i', $punchIn);
                                if ($startC->lt($in)) {
                                    $validator->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                                }
                            } catch (\Exception $e) {}
                        }
                        if ($punchOut) {
                            try {
                                $out = Carbon::createFromFormat('H:i', $punchOut);
                                if ($startC->gt($out)) {
                                    $validator->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                                }
                            } catch (\Exception $e) {}
                        }
                    }

                    if ($endC && $punchOut) {
                        try {
                            $out = Carbon::createFromFormat('H:i', $punchOut);
                            if ($endC->gt($out)) {
                                $validator->errors()->add("breaks.$i.end", '休憩時間もしくは退勤時間が不適切な値です');
                            }
                        } catch (\Exception $e) {}
                    }
                }
            }
        });
    }
}
