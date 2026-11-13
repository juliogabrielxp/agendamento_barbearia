<?php

declare(strict_types=1);

namespace App\Domain;

use DateInterval;
use DateTimeImmutable;

/**
 * Cadastro por e-mail esperando o código de confirmação.
 * A conta só é criada quando o dono do e-mail digita o código que recebeu.
 *
 * Além do código, o cadastro guarda o hash de uma chave aleatória que fica só na sessão
 * de quem começou o cadastro. Sem ela, um terceiro poderia reenviar o formulário com o
 * e-mail da vítima e a própria senha, e a vítima, ao digitar o código, criaria a conta
 * com a senha dele.
 */
final class CadastroPendente
{
    public const VALIDADE_DO_CODIGO_EM_MINUTOS = 15;

    public const MAXIMO_DE_TENTATIVAS = 5;

    public const INTERVALO_ENTRE_ENVIOS_EM_SEGUNDOS = 60;

    public function __construct(
        public readonly string $nome,
        public readonly string $email,
        public readonly string $senhaHash,
        public readonly string $codigoHash,
        public readonly string $chaveHash,
        public readonly int $tentativas,
        public readonly DateTimeImmutable $expiraEm,
        public readonly DateTimeImmutable $enviadoEm,
    ) {}

    public static function iniciar(
        string $nome,
        string $email,
        string $senhaHash,
        string $codigoHash,
        string $chave,
        DateTimeImmutable $agora
    ): self {
        return new self(
            $nome,
            $email,
            $senhaHash,
            $codigoHash,
            self::hashDaChave($chave),
            0,
            self::calcularExpiracao($agora),
            $agora,
        );
    }

    /** Código de 6 dígitos, com zeros à esquerda */
    public static function gerarCodigo(): string
    {
        return str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
    }

    public static function gerarChave(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function hashDaChave(string $chave): string
    {
        // A chave é aleatória e longa: sha256 basta, não precisa de bcrypt
        return hash('sha256', $chave);
    }

    public function pertenceA(string $chave): bool
    {
        return hash_equals($this->chaveHash, self::hashDaChave($chave));
    }

    /** Troca o código e zera as tentativas, mantendo nome, senha e chave */
    public function comNovoCodigo(string $codigoHash, DateTimeImmutable $agora): self
    {
        return new self(
            $this->nome,
            $this->email,
            $this->senhaHash,
            $codigoHash,
            $this->chaveHash,
            0,
            self::calcularExpiracao($agora),
            $agora,
        );
    }

    public function expirou(DateTimeImmutable $agora): bool
    {
        return $agora >= $this->expiraEm;
    }

    public function tentativasEsgotadas(): bool
    {
        return $this->tentativas >= self::MAXIMO_DE_TENTATIVAS;
    }

    /** Segura envios em sequência para ninguém usar o cadastro para encher a caixa de outra pessoa */
    public function segundosParaPoderReenviar(DateTimeImmutable $agora): int
    {
        $liberadoEm = $this->enviadoEm->getTimestamp() + self::INTERVALO_ENTRE_ENVIOS_EM_SEGUNDOS;

        return max(0, $liberadoEm - $agora->getTimestamp());
    }

    private static function calcularExpiracao(DateTimeImmutable $agora): DateTimeImmutable
    {
        return $agora->add(new DateInterval('PT'.self::VALIDADE_DO_CODIGO_EM_MINUTOS.'M'));
    }
}
