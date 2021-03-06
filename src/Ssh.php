<?php

namespace Spatie\Ssh;

use Symfony\Component\Process\Process;

class Ssh
{
    private string $user;

    private string $host;

    private string $pathToPublicKey = '';

    private ?int $port;

    public function __construct(string $user, string $host, int $port = null)
    {
        $this->user = $user;

        $this->host = $host;

        $this->port = $port;
    }

    public function usePublicKey($pathToPublicKey): self
    {
        $this->pathToPublicKey = $pathToPublicKey;

        return $this;
    }

    public function port(int $port): self
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @param string|array $command
     *
     * @return string
     */
    public function getSshCommand($command): string
    {
        $commands = $this->wrapArray($command);

        $extraOptions = $this->getExtraOptions();

        $commandString = implode(PHP_EOL, $commands);

        $delimiter = 'EOF-SPATIE-SSH';

        $target = "{$this->user}@{$this->host}";

        return "ssh {$extraOptions} $target 'bash -se' << \\$delimiter".PHP_EOL
            .$commandString.PHP_EOL
            .$delimiter;
    }

    /**
     * @param string|array $command
     *
     * @return \Symfony\Component\Process\Process
     */
    public function execute($command): Process
    {
        $sshCommand = $this->getSshCommand($command);

        $process = Process::fromShellCommandline($sshCommand);

        $process->setTimeout(0);

        $process->run();

        return $process;
    }

    protected function wrapArray($arrayOrString): array
    {
        return is_array($arrayOrString)
            ? $arrayOrString
            : [$arrayOrString];
    }

    protected function getExtraOptions(): string
    {
        $extraOptions = [];

        if ($this->pathToPublicKey) {
            $extraOptions[] = "-i {$this->pathToPublicKey}";
        }

        if ($this->port) {
            $extraOptions[] = "-p {$this->port}";
        }

        return implode(' ', $extraOptions);
    }
}
