<?php

declare(strict_types=1);

namespace Jot\HfRepository;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Framework\Event;
use Psr\Container\ContainerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

#[Listener]
class RequiredConfigListener implements ListenerInterface
{

    public function __construct(protected ContainerInterface $container)
    {

    }

    public function listen(): array
    {
        return [
            Event\BeforeServerStart::class
        ];
    }

    public function process(object $event): void
    {
        $output = new ConsoleOutput();

        $hasMissingRequiredPackages = false;
        foreach (['hyperf/etcd', 'hyperf/redis', 'hyperf/swagger', 'jot/hf_elastic'] as $package) {
            $configName = explode('/', $package)[1];
            if (!$this->container->get(ConfigInterface::class)->get($configName)) {
                if (!$hasMissingRequiredPackages) {
                    $output->writeln('');
                    $output->writeln(sprintf('<options=bold;fg=red>[ERROR]</> The required packages <options=bold>%s</> are not configured. To proceed, please run the following commands before starting the application:', ucfirst($package)));
                    $output->writeln('');
                }
                $output->writeln(sprintf('    <options=bold>php bin/hyperf.php vendor:publish %s</>', $package));
                $hasMissingRequiredPackages = true;
            }
        }

        if ($hasMissingRequiredPackages) {
            $output->writeln('');
            exit(1);
        }
    }
}
