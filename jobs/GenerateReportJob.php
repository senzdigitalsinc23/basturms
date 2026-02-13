<?php
namespace Jobs;

use Services\Reports\ReportFactory;

class GenerateReportJob
{
    protected array $data;
    protected string $type;
    protected string $filePath;

    public function __construct(array $data, string $type, string $filePath)
    {
        $this->data = $data;
        $this->type = $type;
        $this->filePath = $filePath;
    }

    public function handle(): void
    {
        $report = ReportFactory::create($this->type);
        $report->generate($this->data, $this->filePath);
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }
}
