<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 09.02.19
 * Time: 13:05
 */

namespace alexsisukin\PromoPult\Command;


use alexsisukin\PromoPult\MailRu;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class Mail extends Command
{
    protected static $defaultName = 'mail:list';



    public function configure()
    {
        $this->setDescription('Листинг почты mail.ru')
            ->setHelp('Получаем темы email сообщений с сервиса mail.ru');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Throwable
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $questionEmail = new Question('Введите ваш email:' . PHP_EOL, false);
        $questionPass = new Question('Введите ваш пароль:' . PHP_EOL, false);
        $questionPass->setHidden(true);

        $mailRu = new MailRu();
        $email = $helper->ask($input, $output, $questionEmail);

        $mailRu->setEmail($email);
        $pass = $helper->ask($input, $output, $questionPass);
        $mailRu->setPass($pass);
        $mailRu->auth();
        $table = new Table($output);
        $table->setRows($mailRu->getSubjects());
        $table->render();

    }

}