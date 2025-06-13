<?php

declare(strict_types=1);

namespace Dantweb\Atomizer\Tests\AtomizerModel;

use PHPUnit\Framework\TestCase;
use Dantweb\Atomizer\AtomizerModel\Matrix;
use Dantweb\Atomizer\AtomizerModel\Vector;

class MatrixTest extends TestCase
{
    public function testEmptyMatrixInitialization(): void
    {
        $matrix = new Matrix();
        $this->assertEmpty($matrix->getMatrix(), 'Matrix should be empty upon initialization');
    }

    public function testAddVector(): void
    {
        $matrix = new Matrix();
        $vector = new Vector(); // Adjust the constructor as needed
        $matrix->addVector($vector);

        $storedMatrix = $matrix->getMatrix();

        $this->assertCount(1, $storedMatrix, 'Matrix should contain one vector after adding one');
        $this->assertSame($vector, $storedMatrix[0], 'The vector added should be the same as the one stored in the matrix');
    }

    public function testPresetMatrixInitialization(): void
    {
        $vector1 = new Vector(); // Adjust as needed for proper instantiation
        $vector2 = new Vector();

        $matrix = new Matrix([$vector1, $vector2]);
        $storedMatrix = $matrix->getMatrix();

        $this->assertCount(2, $storedMatrix, 'Matrix should be initialized with two vectors');
        $this->assertSame($vector1, $storedMatrix[0], 'The first vector should match the one provided');
        $this->assertSame($vector2, $storedMatrix[1], 'The second vector should match the one provided');
    }
}