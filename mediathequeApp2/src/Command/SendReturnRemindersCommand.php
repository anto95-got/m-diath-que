<?php

namespace App\Command;

use App\Repository\EmpruntRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:send-return-reminders',
    description: 'Envoie les rappels d\'emprunt à J-30, J-7 et J-1 avant la date de retour',
)]
class SendReturnRemindersCommand extends Command
{
    public function __construct(
        private EmpruntRepository $empruntRepository,
        private MailerInterface $mailer,
        #[Autowire('%env(MAILER_FROM)%')] private string $defaultSender
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $today = new \DateTime('today');

        $emprunts = $this->empruntRepository->findBy(['dateRetourEffectif' => null]);

        foreach ($emprunts as $emprunt) {
            $due = $emprunt->getDateRetourPrevue();
            if (!$due || !$emprunt->getIdUtilisateur() || !$emprunt->getIdUtilisateur()->getEmail()) {
                continue;
            }

            $diffDays = (int) $today->diff($due)->format('%r%a'); // négatif si en retard

            $send = false;
            $subject = '';
            $body = '';

            if ($diffDays === 30) {
                $send = true;
                $subject = 'Rappel : retour de votre emprunt dans 30 jours';
            } elseif ($diffDays === 7) {
                $send = true;
                $subject = 'Rappel : retour de votre emprunt dans 7 jours';
            } elseif ($diffDays === 1) {
                $send = true;
                $subject = 'Rappel : retour de votre emprunt demain';
            }

            if ($send) {
                $body = sprintf(
                    "Bonjour %s,\n\nLe document \"%s\" est à rendre pour le %s.\nIl reste %d jour(s) avant l'échéance.\n\nMerci.",
                    $emprunt->getIdUtilisateur()->getNom() ?? 'utilisateur',
                    $emprunt->getIdDoc()?->getTitre() ?? 'Document',
                    $due->format('d/m/Y'),
                    $diffDays
                );

                $email = (new Email())
                    ->from($this->defaultSender)
                    ->to($emprunt->getIdUtilisateur()->getEmail())
                    ->subject($subject)
                    ->text($body);
                $this->mailer->send($email);
            }
        }

        $output->writeln('Rappels envoyés.');
        return Command::SUCCESS;
    }
}
