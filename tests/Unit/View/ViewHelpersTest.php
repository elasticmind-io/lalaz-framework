<?php

use Lalaz\View\ViewHelpers;
use Lalaz\View\ViewFunction;
use Lalaz\Security\CsrfProtection;

beforeEach(function() {
    // Initialize CSRF for tests
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    CsrfProtection::generateToken();
});

test('ViewHelpers returns all helpers as array', function() {
    $helpers = ViewHelpers::all();

    expect($helpers)->toBeArray()
        ->and(count($helpers))->toBeGreaterThan(0);
});

test('each helper is instance of ViewFunction', function() {
    $helpers = ViewHelpers::all();

    foreach ($helpers as $helper) {
        expect($helper)->toBeInstanceOf(ViewFunction::class);
    }
});

test('asset helper returns ViewFunction with correct name', function() {
    $helper = ViewHelpers::asset();

    expect($helper)->toBeInstanceOf(ViewFunction::class)
        ->and($helper->getName())->toBe('asset')
        ->and($helper->getCallable())->toBeCallable();
});

test('flashMessage helper returns ViewFunction with correct name', function() {
    $helper = ViewHelpers::flashMessage();

    expect($helper)->toBeInstanceOf(ViewFunction::class)
        ->and($helper->getName())->toBe('showFlashMessage')
        ->and($helper->getCallable())->toBeCallable();
});

test('routeUrl helper returns ViewFunction with correct name', function() {
    $helper = ViewHelpers::routeUrl();

    expect($helper)->toBeInstanceOf(ViewFunction::class)
        ->and($helper->getName())->toBe('routeUrl')
        ->and($helper->getCallable())->toBeCallable();
});

test('conditional helper returns ViewFunction with correct name', function() {
    $helper = ViewHelpers::conditional();

    expect($helper)->toBeInstanceOf(ViewFunction::class)
        ->and($helper->getName())->toBe('conditional')
        ->and($helper->getCallable())->toBeCallable();
});

test('renderIf helper returns ViewFunction with correct name', function() {
    $helper = ViewHelpers::renderIf();

    expect($helper)->toBeInstanceOf(ViewFunction::class)
        ->and($helper->getName())->toBe('renderIf')
        ->and($helper->getCallable())->toBeCallable();
});

test('csrfToken helper returns ViewFunction with correct name', function() {
    $helper = ViewHelpers::csrfToken();

    expect($helper)->toBeInstanceOf(ViewFunction::class)
        ->and($helper->getName())->toBe('csrfToken')
        ->and($helper->getCallable())->toBeCallable();
});

test('csrfField helper returns ViewFunction with correct name and options', function() {
    $helper = ViewHelpers::csrfField();

    expect($helper)->toBeInstanceOf(ViewFunction::class)
        ->and($helper->getName())->toBe('csrfField')
        ->and($helper->getCallable())->toBeCallable()
        ->and($helper->getOptions())->toHaveKey('is_safe');
});

test('routeUrl callable returns the action as is', function() {
    $helper = ViewHelpers::routeUrl();
    $callable = $helper->getCallable();

    $result = $callable('/home');
    expect($result)->toBe('/home');
});

test('conditional callable returns left when true', function() {
    $helper = ViewHelpers::conditional();
    $callable = $helper->getCallable();

    $result = $callable(true, 'yes', 'no');
    expect($result)->toBe('yes');
});

test('conditional callable returns right when false', function() {
    $helper = ViewHelpers::conditional();
    $callable = $helper->getCallable();

    $result = $callable(false, 'yes', 'no');
    expect($result)->toBe('no');
});

test('renderIf callable returns value when true', function() {
    $helper = ViewHelpers::renderIf();
    $callable = $helper->getCallable();

    $result = $callable('content', true);
    expect($result)->toBe('content');
});

test('renderIf callable returns null when false', function() {
    $helper = ViewHelpers::renderIf();
    $callable = $helper->getCallable();

    $result = $callable('content', false);
    expect($result)->toBeNull();
});

test('csrfToken callable returns non-empty token', function() {
    $helper = ViewHelpers::csrfToken();
    $callable = $helper->getCallable();

    $token = $callable();
    expect($token)->toBeString()
        ->and(strlen($token))->toBeGreaterThan(0);
});

test('csrfField callable returns HTML input', function() {
    $helper = ViewHelpers::csrfField();
    $callable = $helper->getCallable();

    $html = $callable();
    expect($html)->toBeString()
        ->and($html)->toContain('<input type="hidden"')
        ->and($html)->toContain('name="')
        ->and($html)->toContain('value="');
});

test('asset callable returns empty string when manifest does not exist', function() {
    $helper = ViewHelpers::asset();
    $callable = $helper->getCallable();

    $result = $callable('non-existent.css');
    expect($result)->toBe('');
});

test('all helpers have unique names', function() {
    $helpers = ViewHelpers::all();
    $names = array_map(fn($h) => $h->getName(), $helpers);

    expect(count($names))->toBe(count(array_unique($names)));
});
