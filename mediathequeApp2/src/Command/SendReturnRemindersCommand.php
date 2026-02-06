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
    description: 'Envoie les rappels d\'emprunt (J-5 puis quotidien si en retard)',
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
        $today = new \DateTimeImmutable('today');

        $emprunts = $this->empruntRepository->findBy(['dateRetourReelle' => null]);

        foreach ($emprunts as $emprunt) {
            $due = $emprunt->getDateRetourPrevue();
            if (!$due || !$emprunt->getIdUtilisateur() || !$emprunt->getIdUtilisateur()->getEmail()) {
                continue;
            }

            $diffDays = (int) $today->diff($due)->format('%r%a'); // négatif si en retard

            $send = false;
            $subject = '';
            $body = '';

            if ($diffDays === 5) {
                $send = true;
                $subject = 'Rappel : retour de votre emprunt dans 5 jours';
            } elseif ($diffDays < 0) {
                // en retard : on envoie chaque jour
                $send = true;
                $subject = 'Retard de retour d\'un document';
            } elseif ($diffDays === 4) {
                $send = true;
                $subject = 'Rappel : retour de votre emprunt dans 4 jours';
            }

            if ($send) {
                $body = sprintf(
                    "Bonjour %s,\n\nLe document \"%s\" est à rendre pour le %s.\n%s\n\nMerci.",
                    $emprunt->getIdUtilisateur()->getNom() ?? 'utilisateur',
                    $emprunt->getIdDoc()?->getTitre() ?? 'Document',
                    $due->format('d/m/Y'),
                    $diffDays < 0 ? 'Le document est en retard, merci de le rapporter au plus vite.' : 'Merci de prévoir son retour.'
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
