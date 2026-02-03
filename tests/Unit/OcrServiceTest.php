<?php

namespace Tests\Unit;

use App\Services\OcrService;
use Tests\TestCase;

class OcrServiceTest extends TestCase
{
    public function test_parse_tsv_confidence(): void
    {
        $tsv = "level\tpage_num\tblock_num\tpar_num\tline_num\tword_num\tleft\ttop\twidth\theight\tconf\ttext\n"
            . "5\t1\t1\t1\t1\t1\t100\t100\t50\t20\t95\tHello\n"
            . "5\t1\t1\t1\t1\t2\t160\t100\t40\t20\t85\tWorld\n";

        $service = new OcrService();
        $conf = $service->parseTsvConfidence($tsv);

        $this->assertEquals(90.0, $conf);
    }
}
