<?php

use PHPUnit\Framework\TestCase;
// REMOVIDO: 'use PHPUnit\Framework\Constraint\ConsecutiveParameters;'

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
        // ... (Este teste já estava a passar e continua igual)
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
        // ... (Este teste já estava a passar e continua igual)
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

        // *** CORRIGIDO AQUI ***
        // Trocado 'new ConsecutiveParameters(...)' por 'self::withConsecutive(...)'
        $this->stmtMock->expects($this->exactly(2))
            ->method('bindParam')
            ->with(self::withConsecutive(
                [$this->equalTo(':mensagem'), $this->equalTo('Mensagem atualizada')],
                [$this->equalTo(':id'), $this->equalTo(10)]
            ));

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->assertTrue($this->recado->update());
    }

    public function testDelete(): void
    {
        // ... (Este teste já estava a passar e continua igual)
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
                ['SELECT status FROM recados WHERE id = :id', $stmtReadMock],
                ['UPDATE recados SET status = :status WHERE id = :id', $stmtUpdateMock]
            ]);

        $stmtReadMock->expects($this->once())
            ->method('bindParam')
            ->with($this->equalTo(':id'), $this->equalTo(1));

        $stmtReadMock->expects($this->once())
            ->method('execute');
        $stmtReadMock->expects($this->once())
            ->method('fetch')
            ->willReturn(['status' => $statusAtual]);

        // *** CORRIGIDO AQUI ***
        // Trocado 'new ConsecutiveParameters(...)' por 'self::withConsecutive(...)'
        $stmtUpdateMock->expects($this->exactly(2))
            ->method('bindParam')
            ->with(self::withConsecutive(
                [$this->equalTo(':status'), $this->equalTo($novoStatusEsperado)],
                [$this->equalTo(':id'), $this->equalTo(1)]
            ));
        
        $stmtUpdateMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $resultStatus = $this->recado->toggleFavorite();
        $this->assertEquals($novoStatusEsperado, $resultStatus);
    }
}
?>