<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DesignSystemTest extends TestCase
{
    public function test_app_layout_renders_nav_and_no_titlebar(): void
    {
        Route::get('/x1', fn () => '')->name('home');
        Route::get('/x2', fn () => '')->name('store.index');
        Route::get('/x3', fn () => '')->name('community.index');
        Route::get('/x4', fn () => '')->name('profile.show');
        Route::get('/x5', fn () => '')->name('login');
        app('router')->getRoutes()->refreshNameLookups();

        $this->blade('<x-app-layout title="Test">Hello</x-app-layout>')
            ->assertSee('GPlayNation')
            ->assertSee('Store')
            ->assertSee('Community')
            ->assertSee('Hello')
            ->assertDontSee('traffic');
    }

    public function test_portal_layout_renders_sidebar_nav(): void
    {
        Route::get('/y1', fn () => '')->name('home');
        Route::get('/y2', fn () => '')->name('logout');
        Route::get('/y3', fn () => '')->name('dev.dashboard');
        Route::get('/y4', fn () => '')->name('dev.games.index');
        Route::get('/y5', fn () => '')->name('dev.sales.index');
        Route::get('/y6', fn () => '')->name('dev.promotion.index');
        Route::get('/y7', fn () => '')->name('dev.tags.index');
        Route::get('/y8', fn () => '')->name('dev.payouts.index');
        Route::get('/y9', fn () => '')->name('dev.settings.edit');
        Route::get('/y10', fn () => '')->name('admin.dashboard');
        Route::get('/y11', fn () => '')->name('admin.verification.index');
        Route::get('/y12', fn () => '')->name('admin.moderation.index');
        Route::get('/y13', fn () => '')->name('admin.commission.index');
        Route::get('/y14', fn () => '')->name('admin.payouts.index');
        Route::get('/y15', fn () => '')->name('admin.reports.index');
        app('router')->getRoutes()->refreshNameLookups();

        $this->blade('<x-portal-layout title="T" section="developer">Body</x-portal-layout>')
            ->assertSee('DEVELOPER TOOLS')
            ->assertSee('Dashboard')
            ->assertSee('Body');
    }

    public function test_icon_component_renders_svg(): void
    {
        $this->blade('<x-icon name="search" class="w-5 h-5" />')
            ->assertSee('<svg', escape: false)
            ->assertSee('w-5 h-5', escape: false);
    }

    public function test_toasts_render_flashed_messages(): void
    {
        session()->flash('success', 'Saved!');
        $this->blade('<x-toasts />')->assertSee('Saved!');
    }

    public function test_area_chart_renders_path_from_points(): void
    {
        $points = [['label' => '1', 'value' => 0], ['label' => '2', 'value' => 10], ['label' => '3', 'value' => 4]];
        $this->blade('<x-area-chart :points="$points" />', ['points' => $points])
            ->assertSee('<path', escape: false)
            ->assertSee('1D4ED8', escape: false);
    }
}
