<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Cli\Command;

use AcmePhp\Ssl\Parser\CertificateParser;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class StatusCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('status')
            ->setDescription('List all the certificates handled by Acme PHP')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command list all the certificates stored in the Acme PHP storage.
It also displays useful informations about these such as the certificate validity and issuer.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repository = $this->getRepository();

        /** @var FilesystemInterface $master */
        $master = $this->getContainer()->get('repository.master_storage');

        /** @var CertificateParser $certificateParser */
        $certificateParser = $this->getContainer()->get('ssl.certificate_parser');


        $table = new Table($output);
        $table->setHeaders(['Domain', 'Issuer', 'Valid from', 'Valid to', 'Needs renewal?']);

        $directories = $master->listContents('certs');

        foreach ($directories as $directory) {
            if ($directory['type'] !== 'dir') {
                continue;
            }

            $domain = $directory['basename'];
            $parsedCertificate = $certificateParser->parse($repository->loadDomainCertificate($domain));

            $table->addRow([
                $domain,
                $parsedCertificate->getIssuer(),
                $parsedCertificate->getValidFrom()->format('Y-m-d H:i:s'),
                $parsedCertificate->getValidTo()->format('Y-m-d H:i:s'),
                ($parsedCertificate->getValidTo()->format('U') - time() < 604800) ? '<comment>Yes</comment>' : 'No',
            ]);
        }

        $table->render();
    }
}
