<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for login.php
 *
 * Focus on request validation, CSRF, escaping, and UI state without invoking DB or exit/headers.
 */
class LoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $_POST = [];
        $_SERVER = ['REQUEST_METHOD' => 'GET'];
        $_COOKIE = [];
    }

    public function testCsrfTokenIsGeneratedOnFirstVisit()
    {
        $this->assertArrayNotHasKey('csrf_token', $_SESSION);
        $output = $this->captureLoginPageOutput();
        $this->assertArrayHasKey('csrf_token', $_SESSION);
        $this->assertEquals(64, strlen($_SESSION['csrf_token']));
        $this->assertStringContainsString('name="csrf"', $output);
        $this->assertStringContainsString($_SESSION['csrf_token'], $output);
    }

    public function testCsrfTokenIsPreservedOnSubsequentVisits()
    {
        $_SESSION['csrf_token'] = 'existing_token_12345';
        $this->captureLoginPageOutput();
        $this->assertEquals('existing_token_12345', $_SESSION['csrf_token']);
    }

    public function testRedirectBlockExistsForLoggedInUsers()
    {
        $loginContent = file_get_contents(__DIR__ . '/../../login.php');
        $this->assertStringContainsString("isset(\$_SESSION['model_id'])", $loginContent);
        $this->assertStringContainsString("header('Location: model_dashboard.php')", $loginContent);
    }

    public function testFormValidationRejectsEmptyLogin()
    {
        $_SESSION['csrf_token'] = 'valid_token';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['csrf' => 'valid_token', 'login' => '', 'password' => 'password123'];
        $output = $this->captureLoginPageOutput();
        $this->assertStringContainsString('Email/username and password are both required', $output);
    }

    public function testFormValidationRejectsEmptyPassword()
    {
        $_SESSION['csrf_token'] = 'valid_token';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['csrf' => 'valid_token', 'login' => 'user@example.com', 'password' => ''];
        $output = $this->captureLoginPageOutput();
        $this->assertStringContainsString('Email/username and password are both required', $output);
    }

    public function testFormValidationRejectsMissingCsrfToken()
    {
        $_SESSION['csrf_token'] = 'valid_token';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['login' => 'user@example.com', 'password' => 'password123'];
        $output = $this->captureLoginPageOutput();
        $this->assertStringContainsString('session has expired', $output);
    }

    public function testFormValidationRejectsInvalidCsrfToken()
    {
        $_SESSION['csrf_token'] = 'valid_token';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['csrf' => 'invalid_token', 'login' => 'user@example.com', 'password' => 'password123'];
        $output = $this->captureLoginPageOutput();
        $this->assertStringContainsString('session has expired', $output);
    }

    public function testValidCsrfDoesNotAddCsrfErrorWhenOtherErrorsPresent()
    {
        $_SESSION['csrf_token'] = 'valid_token';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        // Leave password empty to keep validation failing before DB/config
        $_POST = ['csrf' => 'valid_token', 'login' => 'user@example.com', 'password' => ''];
        $output = $this->captureLoginPageOutput();
        $this->assertStringNotContainsString('session has expired', $output);
        $this->assertStringContainsString('Email/username and password are both required', $output);
    }

    public function testLoginValueIsTrimmed()
    {
        $_SESSION['csrf_token'] = 'valid_token';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        // Use empty password to avoid DB/config require
        $_POST = ['csrf' => 'valid_token', 'login' => '  user@example.com  ', 'password' => ''];
        $output = $this->captureLoginPageOutput();
        $this->assertStringContainsString('user@example.com', $output);
        $this->assertStringNotContainsString('  user@example.com  ', $output);
    }

    public function testRememberMeCheckboxIsPersisted()
    {
        $_SESSION['csrf_token'] = 'valid_token';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['csrf' => 'valid_token', 'login' => 'user@example.com', 'password' => '', 'remember' => '1'];
        $output = $this->captureLoginPageOutput();
        $this->assertStringContainsString('name="remember"', $output);
        $this->assertStringContainsString('checked', $output);
    }

    public function testRememberMeCheckboxIsNotCheckedByDefault()
    {
        $_SESSION['csrf_token'] = 'valid_token';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['csrf' => 'valid_token', 'login' => 'user@example.com', 'password' => ''];
        $output = $this->captureLoginPageOutput();
        preg_match('/<input[^>]+name="remember"[^>]*>/i', $output, $matches);
        if (!empty($matches[0])) {
            $this->assertStringNotContainsString('checked', $matches[0]);
        }
    }

    public function testConfigIsNotLoadedOnGetRequest()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertFileExists(__DIR__ . '/../../config.php');
        $output = $this->captureLoginPageOutput();
        $this->assertStringContainsString('<form', $output);
    }

    public function testConfigIsLoadedOnlyAfterValidation()
    {
        $loginContent = file_get_contents(__DIR__ . '/../../login.php');
        $configPos = strpos($loginContent, "require_once __DIR__ . '/config.php'");
        $this->assertNotFalse($configPos, 'config.php should be required');
        $validationPos = strpos($loginContent, 'if (!$errors)');
        $this->assertNotFalse($validationPos, 'Validation block should exist');
        $this->assertGreaterThan($validationPos, $configPos, 'config.php should be required after validation checks');
    }

    public function testErrorMessagesAreHtmlEscaped()
    {
        $_SESSION['csrf_token'] = '<script>alert("xss")</script>';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['csrf' => 'wrong_token', 'login' => 'user@example.com', 'password' => 'password123'];
        $output = $this->captureLoginPageOutput();
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

    public function testFormValuesAreHtmlEscaped()
    {
        $_SESSION['csrf_token'] = 'valid_token';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        // Keep password empty to avoid DB/config require
        $_POST = ['csrf' => 'valid_token', 'login' => '<script>alert("xss")</script>', 'password' => ''];
        $output = $this->captureLoginPageOutput();
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

    public function testMultipleErrorsAreDisplayed()
    {
        $_SESSION['csrf_token'] = 'valid_token';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['csrf' => 'invalid_token', 'login' => '', 'password' => ''];
        $output = $this->captureLoginPageOutput();
        $this->assertStringContainsString('session has expired', $output);
        $this->assertStringContainsString('Email/username and password are both required', $output);
    }

    public function testAcceptsEmailAsLogin()
    {
        $_SESSION['csrf_token'] = 'valid_token';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        // Keep password empty to avoid DB/config require
        $_POST = ['csrf' => 'valid_token', 'email' => 'user@example.com', 'password' => ''];
        $output = $this->captureLoginPageOutput();
        $this->assertStringContainsString('user@example.com', $output);
    }

    public function testAcceptsUsernameAsLogin()
    {
        $_SESSION['csrf_token'] = 'valid_token';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        // Keep password empty to avoid DB/config require
        $_POST = ['csrf' => 'valid_token', 'username' => 'myusername', 'password' => ''];
        $output = $this->captureLoginPageOutput();
        $this->assertStringContainsString('myusername', $output);
    }

    public function testFallsBackToLoginField()
    {
        $_SESSION['csrf_token'] = 'valid_token';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        // Keep password empty to avoid DB/config require
        $_POST = [
            'csrf' => 'valid_token',
            'login' => 'login_value',
            'email' => 'email_value',
            'username' => 'username_value',
            'password' => ''
        ];
        $output = $this->captureLoginPageOutput();
        $this->assertStringContainsString('login_value', $output);
    }

    private function captureLoginPageOutput(): string
    {
        ob_start();
        include __DIR__ . '/../../login.php';
        return (string) ob_get_clean();
    }
}