<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 10.02.19
 * Time: 2:06
 */

namespace alexsisukin\PromoPult\Command;


use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Regex extends Command
{
    protected static $defaultName = 'regex:mail.ru';


    public function configure()
    {
        $this->setDescription('Grep главной страницы mail.ru')
            ->setHelp('Получаем картинки из img и style');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {


        $client = new Client([
            'base_uri' => 'https://mail.ru',
            'timeout' => 10.0,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) ' .
                    'Ubuntu Chromium/71.0.3578.98 ' .
                    'Chrome/71.0.3578.98 Safari/537.36',
            ]
        ]);
        $response = $client->get('/');
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Не смогли получить контент');
        }
        $pattern = '~(<img[\s.]+src[\s]*=[\s]*["\'](.+?)["\']|url[\s]*\([\s]*["\'](.+?)["\'])~ui';
        $content = $response->getBody()->getContents();
        $content = preg_replace('~<[\s]*script[\s\w.\W]+?\<[\s]*\/[\s]*script[\s]*>~ui', '', $content);

        preg_match_all($pattern, $content, $matches);
        if (empty($matches['2'])) {
            throw new \Exception('Ни чего не найдено');
        }

        $images = array_merge($matches[2], $matches[3]);
        $images = array_filter($images, function ($element) {
            return !empty($element);
        });
        $i = 0;
        foreach ($images as $item) {
            $i++;
            $output->writeln($i . '. ' . $item . ';');
        }


    }
}