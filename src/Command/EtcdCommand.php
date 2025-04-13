<?php

declare(strict_types=1);
/**
 * This file is part of the hf_repository module, a package build for Hyperf framework that is responsible for
 * manage controllers, entities and repositories.
 *
 * @author   Joao Zanon <jot@jot.com.br>
 * @link     https://github.com/JotJunior/hf-repository
 * @license  MIT
 */

namespace Jot\HfRepository\Command;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Etcd\KVInterface;
use Psr\Container\ContainerInterface;

#[Command]
class EtcdCommand extends HyperfCommand
{
    #[Inject]
    protected ConfigInterface $config;

    #[Inject]
    protected KVInterface $etcd;

    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('etcd:put');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription(__('hf-repository.command.etcd_description'));
        $this->addArgument('config-key', null, __('hf-repository.command.config_key_description'));
    }

    public function handle()
    {
        $configKey = $this->input->getArgument('config-key');
        $etcdKey = sprintf('/application/%s', $configKey);

        $etcdData = $this->etcd->get($etcdKey);
        $etcdKeyExists = ! empty($etcdData['kvs'][0]['value']);

        $confirmation = $this->ask(__('hf-repository.command.etcd_key_exists', ['key' => $etcdKey]), 'N');
        if ($etcdKeyExists && strtolower($confirmation) === 'n') {
            $this->line('Aborted.');
            return;
        }

        $this->etcd->put($etcdKey, json_encode($this->config->get($configKey), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
