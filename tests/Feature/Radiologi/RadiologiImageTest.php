<?php

namespace Tests\Feature\Radiologi;

use App\Models\Setting;
use App\Providers\RuntimeSettingsServiceProvider;
use App\Support\RadiologiImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RadiologiImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_url_is_null_when_base_url_is_not_configured(): void
    {
        config(['radiologi.image_base_url' => null]);

        $this->assertFalse(RadiologiImage::isConfigured());
        $this->assertNull(RadiologiImage::url('pages/upload/foto.jpg'));
    }

    public function test_url_joins_base_url_and_encodes_the_relative_path(): void
    {
        config(['radiologi.image_base_url' => 'http://khanza.test/webapps/radiologi/']);

        $this->assertSame(
            'http://khanza.test/webapps/radiologi/pages/upload/thorax%20pa.jpg',
            RadiologiImage::url('\\pages/upload/thorax pa.jpg'),
        );
        $this->assertNull(RadiologiImage::url(''));
        $this->assertNull(RadiologiImage::url(null));
    }

    public function test_saved_setting_overrides_the_configured_base_url(): void
    {
        config(['radiologi.image_base_url' => 'http://from-env.test']);

        Setting::current()->update(['radiologi_image_base_url' => 'http://from-settings.test/radiologi']);

        (new RuntimeSettingsServiceProvider($this->app))->boot();

        $this->assertSame('http://from-settings.test/radiologi', config('radiologi.image_base_url'));
    }
}
