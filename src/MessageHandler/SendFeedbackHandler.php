<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Dto\FeedbackDto;
use App\Mailer\Mailer;
use App\Message\SendFeedback;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SendFeedbackHandler implements MessageHandlerInterface
{
    private const FROM_EMAIL_FORMAT_PATTERN = <<<HTML
    <html>

    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//UK" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <meta http-equiv="Content-Type" content="text/html charset=UTF-8" >
    <body>
        <bold>email from %s<span>s</span></bold>
        <h2>
          email text
        </h2>
        <p>
          %s
        </p>
    </body>
    </html>
    HTML;

    /**
     * @var Mailer
     */
    private $mailer;

    private ?string $senderEmail;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(Mailer $mailer, TranslatorInterface $translator, string $senderEmail = null)
    {
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->senderEmail = $senderEmail;
    }

    public function __invoke(SendFeedback $sendFeedback)
    {
        /** @var FeedbackDto $feedback */
        $feedback = $sendFeedback->getFeedback();

        $subject = $this->translator->trans('email.new_message');

        $email = (new Email())
            ->from(new Address($this->senderEmail ?? $feedback->getFromEmail(), $feedback->getFromName()))
            ->to($feedback->getToEmail())
            ->replyTo($feedback->getFromEmail())
            ->subject($subject)
//            ->text($feedback->getMessage())
            ->html(sprintf(
                self::FROM_EMAIL_FORMAT_PATTERN,
                $feedback->getFromName(),
                $feedback->getFromEmail()
        ));

        $this->mailer->send($email);
    }
}
