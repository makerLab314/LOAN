<?php

namespace App\Http\Controllers;

use App\Support\Settings;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function imprint(Settings $settings): View
    {
        return $this->page(__('Impressum'), (string) $settings->get('legal.imprint'));
    }

    public function privacy(Settings $settings): View
    {
        return $this->page(__('Datenschutz'), (string) $settings->get('legal.privacy'));
    }

    private function page(string $title, string $markdown): View
    {
        // Markdown ohne HTML und ohne javascript:-Links rendern
        $html = trim($markdown) === '' ? null : Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]);

        return view('legal', compact('title', 'html'));
    }
}
