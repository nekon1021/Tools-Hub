<?php

namespace App\Http\Controllers\Tools\Count;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tool\CountRequest;
use App\Services\Tool\TextCounter;
use Illuminate\Http\RedirectResponse;

class CountController extends Controller
{
    public function count()
    {
        return view('tools.character-count.count');
    }

    public function countrun(CountRequest $request, TextCounter $counter): RedirectResponse
    {
        $result = $counter->countCharsAndLines($request->validated()['text']);
        return back()->withInput()->with('count_result', $result);
    }

    public function countreset()
    {
        session()->forget(['count_result', '_old_input']);
        return redirect()->route('tools.charcount');
    }
}
