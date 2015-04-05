<?php
/**
 * Created by IntelliJ IDEA.
 * User: indy
 * Date: 06/04/15
 * Time: 01:15
 */

namespace Freefeed\Website\Models;


use Freefeed\Website\Application;
use Hackzilla\PasswordGenerator\Generator\ComputerPasswordGenerator;

class EmailValidation
{
    /** @var \Doctrine\DBAL\Connection */
    private $db;

    public function __construct(Application $app)
    {
        $this->db = $app['db'];
    }

    /**
     * @param int $uid
     * @return string
     */
    public function create($uid)
    {
        $generator = new ComputerPasswordGenerator();
        $generator
            ->setLength(64)
            ->setUppercase()->setLowercase()->setNumbers()
            ->setSymbols(false);

        $secret = $generator->generatePassword();

        $this->db->insert('email_validation', ['user_id' => $uid, 'secret_link' => $secret]);

        return $secret;
    }

    /**
     * @param string $secret
     * @return bool|int
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
     */
    public function validate($secret)
    {
        $stmt = $this->db->executeQuery('SELECT `user_id` FROM `email_validation` WHERE `secret_link`=?', [$secret]);
        $uid = $stmt->fetchColumn(0);

        if ($uid === false) {
            return false;
        }

        $this->db->delete('email_validation', ['user_id' => $uid]);
        return $uid;
    }

    public function deleteById($id)
    {
        $this->db->delete('email_validation', ['id' => $id]);
    }
}
