<?php

class Middleware
{
    public static function auth(): bool
    {
        if (!Auth::check()) {
            Helper::redirect('/login');
            return false;
        }
        return true;
    }

    public static function admin(): bool
    {
        if (!Auth::check()) {
            Helper::redirect('/login');
            return false;
        }
        if (Auth::role() !== 'admin') {
            Helper::flash('error', 'دسترسی غیرمجاز.');
            Helper::redirect('/dashboard');
            return false;
        }
        return true;
    }

    public static function reporter(): bool
    {
        if (!Auth::check()) {
            Helper::redirect('/login');
            return false;
        }
        $role = Auth::role();
        if (!in_array($role, ['reporter', 'admin'], true)) {
            Helper::flash('error', 'دسترسی غیرمجاز.');
            Helper::redirect('/dashboard');
            return false;
        }
        return true;
    }

    public static function photographer(): bool
    {
        if (!Auth::check()) {
            Helper::redirect('/login');
            return false;
        }
        $role = Auth::role();
        if (!in_array($role, ['photographer', 'admin'], true)) {
            Helper::flash('error', 'دسترسی غیرمجاز.');
            Helper::redirect('/dashboard');
            return false;
        }
        return true;
    }

    public static function guest(): bool
    {
        if (Auth::check()) {
            Helper::redirect('/dashboard');
            return false;
        }
        return true;
    }
}
