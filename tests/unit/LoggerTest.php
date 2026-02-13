<?php

use PHPUnit\Framework\TestCase;
use App\Core\Logger;

class LoggerTest extends TestCase
{
    private string $logDir;

    protected function setUp(): void
    {
        $this->logDir = sys_get_temp_dir() . '/logger_test_' . uniqid();
        mkdir($this->logDir, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->logDir . '/*') as $file) {
            @unlink($file);
        }
        @rmdir($this->logDir);
    }

    public function testLogLevelsAndFormats()
    {
        $logger = new Logger($this->logDir);
        $logger->debug('Debug message');
        $logger->info('Info message');
        $logger->notice('Notice message');
        $logger->warning('Warning message');
        $logger->error('Error message');
        $logger->critical('Critical message');
        $logger->alert('Alert message');
        $logger->emergency('Emergency message');

        $today = date('Y-m-d');
        $logFile = $this->logDir . '/app.log.' . $today . '.log';
        $this->assertFileExists($logFile);
        $contents = file_get_contents($logFile);
        $this->assertStringContainsString('DEBUG: Debug message', $contents);
        $this->assertStringContainsString('INFO: Info message', $contents);
        $this->assertStringContainsString('NOTICE: Notice message', $contents);
        $this->assertStringContainsString('WARNING: Warning message', $contents);
        $this->assertStringContainsString('ERROR: Error message', $contents);
        $this->assertStringContainsString('CRITICAL: Critical message', $contents);
        $this->assertStringContainsString('ALERT: Alert message', $contents);
        $this->assertStringContainsString('EMERGENCY: Emergency message', $contents);
    }

    public function testJsonFormat()
    {
        $logger = new Logger($this->logDir, true);
        $logger->info('Json test', ['foo' => 'bar']);
        $today = date('Y-m-d');
        $logFile = $this->logDir . '/app.log.' . $today . '.log';
        $this->assertFileExists($logFile);
        $lines = file($logFile, FILE_IGNORE_NEW_LINES);
        $entry = json_decode($lines[0], true);
        $this->assertEquals('INFO', $entry['level']);
        $this->assertEquals('Json test', $entry['message']);
        $this->assertEquals(['foo' => 'bar'], $entry['context']);
    }

    public function testFileSizeRotation()
    {
        $logger = new Logger($this->logDir);
        $today = date('Y-m-d');
        $logFile = $this->logDir . '/app.log.' . $today . '.log';
        // Write enough to exceed 5MB
        $msg = str_repeat('A', 1024);
        for ($i = 0; $i < 5200; $i++) {
            $logger->info($msg);
        }
        clearstatcache();
        // Debug: list files in log dir
        $files = glob($this->logDir . '/*');
        fwrite(STDERR, "\nLog dir: {$this->logDir}\nFiles: " . print_r($files, true) . "\n");
        foreach ($files as $f) {
            fwrite(STDERR, "$f size: " . filesize($f) . "\n");
        }
        $this->assertFileExists($logFile);
        $rotatedFiles = glob($logFile . '.*');
        fwrite(STDERR, "Rotated files: " . print_r($rotatedFiles, true) . "\n");
        $this->assertNotEmpty($rotatedFiles, 'No rotated log files found');
    }
}
