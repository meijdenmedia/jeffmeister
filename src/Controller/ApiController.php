<?php
namespace App\Controller;

use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class ApiController
 * @package App\Controller
 */
class ApiController extends AbstractController
{
    /**
     * @var string
     */
    private $question;

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Telegram hook for Jeffmeister bot. Make sure you
     * have a .env file with the following vars:
     * - TELEGRAM_BOT_API_KEY
     * - TELEGRAM_BOT_USERNAME
     *
     * (or copy from .env.example)
     *
     * @Route("/telegram/hook", name="telegram_hook", methods={"POST"})
     *
     * @param LoggerInterface $logger
     * @return Response
     */
    public function hook(LoggerInterface $logger)
    {
        $data = $this->getRequest();
        $this->question = strtolower($data['message']['text']);

        try {
            $telegram = new Telegram($_ENV['TELEGRAM_BOT_API_KEY'], $_ENV['TELEGRAM_BOT_USERNAME']);
            $telegram->useGetUpdatesWithoutDatabase();

            switch (true) {
                case $this->checkCommand('/credits'):
                    $this->sendTextReply('Deze bot doet niks spannends pakt plaatje van ftp en plaatst die om 7:00', $logger);
                    break;
                case $this->checkCommand('/debug'):
                    $this->sendTextReply(print_r($data, true), $logger);
                    break;
            }
        } catch (TelegramException $e) {
            $logger->error($e->getMessage());
        }

        return new Response(
            'OK'
        );
    }

    /**
     * If you want to set up a hook for your own test purposes.
     *
     * @Route("/telegram/set-hook", name="telegram_set_hook", methods={"GET"})
     *
     * @return Response
     * @throws TelegramException
     */
    public function setHook()
    {
        $telegram = new Telegram($_ENV['TELEGRAM_BOT_API_KEY'], $_ENV['TELEGRAM_BOT_USERNAME']);
        try {
            $result = $telegram->setWebhook($_ENV['TELEGRAM_BASE_URL'] . $this->router->generate('telegram_hook'));

            if ($result->isOk()) {
                return new Response(
                    $result->getDescription()
                );
            }
        } catch (TelegramException $e) {
            return new Response(
                $e->getMessage()
            );
        }
        return new Response(
            'Something went wrong'
        );
    }

    /**
     * @param string $command
     * @return bool
     */
    private function checkCommand(string $command)
    {
        return (substr($this->question, 0, strlen($command)) == $command);
    }

    /**
     * @param string $reply
     * @param LoggerInterface $logger
     */
    private function sendTextReply(string $reply, LoggerInterface $logger)
    {
        $data = $this->getRequest();

        if (isset($data['message']['chat']['id'])) {
            try {
                \Longman\TelegramBot\Request::sendMessage([
                    'chat_id' => $data['message']['chat']['id'],
                    'text' => $reply,
                ]);
            } catch (TelegramException $e) {
                $logger->error($e->getMessage());
            }
        }
    }

    /**
     * @return mixed
     */
    private function getRequest()
    {
        return json_decode(file_get_contents('php://input'), true);
    }
}