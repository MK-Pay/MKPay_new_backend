<?php

namespace Tests\Feature;

use App\Mail\ErrorReportMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class ErrorReportMailTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itCreatesErrorReportMailWithExceptionAndContext(): void
    {
        $exception = new RuntimeException('Test error message', 500);
        $context = [
            'url' => 'http://localhost/test',
            'method' => 'POST',
            'user_id' => 1,
            'tenant' => 'test-tenant',
        ];

        $mailable = new ErrorReportMail($exception, $context);

        $this->assertInstanceOf(ErrorReportMail::class, $mailable);
        $this->assertSame($exception, $mailable->exception);
        $this->assertSame($context, $mailable->context);
    }

    #[Test]
    public function itGeneratesCorrectSubjectLine(): void
    {
        $exception = new RuntimeException('Test error');
        $context = [];

        $mailable = new ErrorReportMail($exception, $context);
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('Error: RuntimeException', $envelope->subject);
        $this->assertStringContainsString('[' . config('app.env') . ']', $envelope->subject);
    }

    #[Test]
    public function itUsesCorrectView(): void
    {
        $exception = new RuntimeException('Test error');
        $context = [];

        $mailable = new ErrorReportMail($exception, $context);
        $content = $mailable->content();

        $this->assertEquals('emails.error-report', $content->view);
    }

    #[Test]
    public function itRendersHtmlWithExceptionDetails(): void
    {
        $exception = new RuntimeException('Critical database failure', 500);
        $context = [
            'url' => 'http://localhost/api/users',
            'method' => 'POST',
            'user_id' => 123,
            'tenant' => 'production-tenant',
        ];

        $mailable = new ErrorReportMail($exception, $context);
        $html = $mailable->render();

        // Check that HTML contains error information
        $this->assertStringContainsString('Error Report', $html);
        $this->assertStringContainsString('RuntimeException', $html);
        $this->assertStringContainsString('Critical database failure', $html);

        // Check that context is included
        $this->assertStringContainsString('http://localhost/api/users', $html);
        $this->assertStringContainsString('POST', $html);

        // Check for structural elements
        $this->assertStringContainsString('Stack Trace', $html);
        $this->assertStringContainsString('Error Information', $html);
        $this->assertStringContainsString('Application Context', $html);
    }

    #[Test]
    public function itHandlesEmptyContext(): void
    {
        $exception = new RuntimeException('Test error');
        $context = [];

        $mailable = new ErrorReportMail($exception, $context);
        $html = $mailable->render();

        $this->assertStringContainsString('Error Report', $html);
        $this->assertStringContainsString('RuntimeException', $html);
    }

    #[Test]
    public function itIncludesRequestDataWhenProvided(): void
    {
        $exception = new RuntimeException('Test error');
        $context = [
            'url' => 'http://localhost/api/test',
            'method' => 'POST',
            'request_data' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
        ];

        $mailable = new ErrorReportMail($exception, $context);
        $html = $mailable->render();

        $this->assertStringContainsString('Request Data', $html);
        $this->assertStringContainsString('John Doe', $html);
        $this->assertStringContainsString('john@example.com', $html);
    }

    #[Test]
    public function itShowsDifferentExceptionTypes(): void
    {
        $exceptions = [
            new RuntimeException('Runtime error'),
            new \LogicException('Logic error'),
            new \InvalidArgumentException('Invalid argument'),
        ];

        foreach ($exceptions as $exception) {
            $mailable = new ErrorReportMail($exception, []);
            $html = $mailable->render();

            $this->assertStringContainsString(get_class($exception), $html);
        }
    }

    #[Test]
    public function itErrorEmailRouteWorks(): void
    {
        $response = $this->get('/dev/test-error-email');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Test error email sent successfully',
        ]);
    }
}
