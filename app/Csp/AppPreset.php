<?php

namespace App\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;

class AppPreset implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::SCRIPT, Keyword::SELF)
            ->addNonce(Directive::SCRIPT)
            ->add(Directive::STYLE, [Keyword::SELF, Keyword::UNSAFE_INLINE])
            ->add(Directive::STYLE, ['https://fonts.bunny.net', 'https://fonts.googleapis.com'])
            ->add(Directive::IMG, [Keyword::SELF, 'data:', 'blob:', 'https://*.r2.dev'])
            ->add(Directive::FONT, [Keyword::SELF, 'https://fonts.bunny.net', 'https://fonts.gstatic.com'])
            ->add(Directive::CONNECT, Keyword::SELF)
            ->add(Directive::OBJECT, Keyword::NONE)
            ->add(Directive::FRAME_ANCESTORS, Keyword::NONE)
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::FORM_ACTION, Keyword::SELF);
    }
}
