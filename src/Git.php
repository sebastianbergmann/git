<?php
/*
 * This file is part of Git.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\Git;

use DateTime;

/**
 */
class Git
{
    /**
     * @var string
     */
    private $repositoryPath;

    /**
     * @param string $repositoryPath
     */
    public function __construct($repositoryPath)
    {
        $this->repositoryPath = realpath($repositoryPath);
    }

    /**
     * @param string $revision
     */
    public function checkout($revision)
    {
        $this->execute(
            'git checkout --force --quiet ' . $revision . ' 2>&1'
        );
    }

    /**
     * @return string
     */
    public function getCurrentBranch()
    {
        $output = $this->execute('git symbolic-ref HEAD');

        $tmp = explode('/', $output[0]);

        return $tmp[2];
    }

    /**
     * @param  string $from
     * @param  string $to
     * @return string
     */
    public function getDiff($from, $to)
    {
        $output = $this->execute(
            'git diff --no-ext-diff ' . $from . ' ' . $to
        );

        return implode("\n", $output);
    }

    /**
     * @return array
     */
    public function getRevisions()
    {
        $output = $this->execute(
            'git log --no-merges --date-order --reverse --format=medium'
        );

        $numLines  = count($output);
        $revisions = array();

        for ($i = 0; $i < $numLines; $i++) {
            $tmp = explode(' ', $output[$i]);

            $author = '';
            $sha1 = '';
            if (count($tmp) == 2 && $tmp[0] == 'commit') {
                $sha1 = $tmp[1];
            } elseif (count($tmp) == 4 && $tmp[0] == 'Author:') {
                $author = implode(' ', array_slice($tmp, 1));
            } elseif (count($tmp) == 9 && $tmp[0] == 'Date:') {
                $revisions[] = array(
                  'author'  => $author,
                  'date'    => DateTime::createFromFormat(
                      'D M j H:i:s Y O',
                      implode(' ', array_slice($tmp, 3))
                  ),
                  'sha1'    => $sha1,
                  'message' => isset($output[$i+2]) ? trim($output[$i+2]) : ''
                );
            }
        }

        return $revisions;
    }

    /**
     * @param  string           $command
     * @throws RuntimeException
     */
    protected function execute($command)
    {
        $cwd = getcwd();
        chdir($this->repositoryPath);
        exec($command, $output, $returnValue);
        chdir($cwd);

        if ($returnValue !== 0) {
            throw new RuntimeException(implode("\r\n", $output));
        }

        return $output;
    }
}
