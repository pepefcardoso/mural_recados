<?php

use PHPUnit\Framework\TestCase;

include_once __DIR__ . '/../api/models/Recado.php';

class RecadoTest extends TestCase
{
    private $dbMock;
    private $stmtMock;
    private $recado;

    protected function setUp(): void
    {
        $this->dbMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        $this->recado = new Recado($this->dbMock);
    }

    public function testReadOrdenaPorStatusCorretamente(): void
    {
        $expectedSql = 'SELECT id, mensagem, status, data_criacao FROM recados ORDER BY status DESC, data_criacao DESC';

        $this->dbMock->expects($this->once())
            ->method('prepare')
            ->with($this->equalTo($expectedSql))
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->recado->read();
    }

    public function testCreateUsaBindParamParaSeguranca(): void
    {
        $mensagemComSimbolos = '<script>alert(1)</script> & Símbolos';
        $this->recado->mensagem = $mensagemComSimbolos;

        $expectedSql = 'INSERT INTO recados (mensagem) VALUES (:mensagem)';

        $this->dbMock->expects($this->once())
            ->method('prepare')
            ->with($this->equalTo($expectedSql))
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('bindParam')
            ->with($this->equalTo(':mensagem'), $this->equalTo($mensagemComSimbolos));

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $result = $this->recado->create();
        $this->assertTrue($result);
    }

    public function testUpdateUsaBindParamCorretamente(): void
    {
        $this->recado->id = 10;
        $this->recado->mensagem = 'Mensagem atualizada';

        $expectedSql = 'UPDATE recados SET mensagem = :mensagem WHERE id = :id';

        $this->dbMock->expects($this->once())
            ->method('prepare')
            ->with($expectedSql)
            ->willReturn($this->stmtMock);

        $callCount = 0;
        $this->stmtMock->expects($this->exactly(2))
            ->method('bindParam')
            ->willReturnCallback(function ($param, &$value) use (&$callCount) {
                $callCount++;

                if ($callCount === 1) {
                    $this->assertEquals(':mensagem', $param);
                    $this->assertEquals('Mensagem atualizada', $value);
                } elseif ($callCount === 2) {
                    $this->assertEquals(':id', $param);
                    $this->assertEquals(10, $value);
                }

                return true;
            });

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->assertTrue($this->recado->update());
    }

    public function testDelete(): void
    {
        $this->recado->id = 5;

        $expectedSql = 'DELETE FROM recados WHERE id = :id';

        $this->dbMock->expects($this->once())
            ->method('prepare')
            ->with($expectedSql)
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('bindParam')
            ->with($this->equalTo(':id'), $this->equalTo(5));

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->assertTrue($this->recado->delete());
    }

    public function testToggleFavoriteDeZeroParaUm(): void
    {
        $this->recado->id = 1;
        $statusAtual = 0;
        $novoStatusEsperado = 1;

        $stmtReadMock = $this->createMock(PDOStatement::class);
        $stmtUpdateMock = $this->createMock(PDOStatement::class);

        $this->dbMock->method('prepare')
            ->willReturnMap([
                ['SELECT status FROM recados WHERE id = :id', [], $stmtReadMock],
                ['UPDATE recados SET status = :status WHERE id = :id', [], $stmtUpdateMock]
            ]);

        $stmtReadMock->expects($this->once())
            ->method('bindParam')
            ->with($this->equalTo(':id'), $this->equalTo(1));

        $stmtReadMock->expects($this->once())
            ->method('execute');

        $stmtReadMock->expects($this->once())
            ->method('fetch')
            ->willReturn(['status' => $statusAtual]);

        $callCount = 0;
        $stmtUpdateMock->expects($this->exactly(2))
            ->method('bindParam')
            ->willReturnCallback(function ($param, &$value) use (&$callCount, $novoStatusEsperado) {
                $callCount++;

                if ($callCount === 1) {
                    $this->assertEquals(':status', $param);
                    $this->assertEquals($novoStatusEsperado, $value);
                } elseif ($callCount === 2) {
                    $this->assertEquals(':id', $param);
                    $this->assertEquals(1, $value);
                }

                return true;
            });

        $stmtUpdateMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $resultStatus = $this->recado->toggleFavorite();
        $this->assertEquals($novoStatusEsperado, $resultStatus);
    }
}
