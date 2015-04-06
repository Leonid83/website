<?php
namespace Freefeed\Website\Models;


use Freefeed\Website\Application;

class User
{
    /** @var \Doctrine\DBAL\Connection */
    private $db;

    public function __construct(Application $app)
    {
        $this->db = $app['db'];
    }

    /**
     * @param string $freefeed_username
     * @param string $friendfeed_username
     * @param string $email
     * @param string $clio_api_token
     * @param bool   $backup_me
     * @param bool   $restore_me
     *
     * @return int
     */
    public function register($freefeed_username, $friendfeed_username, $email, $clio_api_token, $backup_me, $restore_me)
    {
        $this->db->insert('users', [
            'freefeed_username'     => $freefeed_username,
            'friendfeed_username'   => $friendfeed_username,
            'email'                 => $email,
            'clio_api_token'        => $clio_api_token,
            'backup_me'             => $backup_me,
            'freefeed_status'       => $restore_me ? 'in' : 'undecided'
        ]);

        return $this->db->lastInsertId();
    }

    public function setPassword($uid, $password_hash)
    {
        $this->db->update(
            'users',
            ['password' => $password_hash],
            ['id' => $uid]
        );
    }

    public function setEmailValidatedAndPassword($uid, $password_hash)
    {
        $this->db->update(
            'users',
            [
                'password' => $password_hash,
                'email_validated' => true,
            ],
            ['id' => $uid]
        );
    }

    /**
     * @param string $email
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function emailIsTaken($email)
    {
        $query = 'SELECT count(*) FROM `users` WHERE `email`=?';
        $stmt = $this->db->executeQuery($query, [$email]);

        return ($stmt->fetchColumn(0) > 0);
    }

    public function friendfeedNameIsTaken($name)
    {
        $query = 'SELECT count(*) FROM `users` WHERE `friendfeed_username`=?';
        $stmt = $this->db->executeQuery($query, [$name]);

        return ($stmt->fetchColumn(0) > 0);
    }

    public function freefeedNameIsTaken($name)
    {
        $query = 'SELECT count(*) FROM `users` WHERE `freefeed_username`=?';
        $stmt = $this->db->executeQuery($query, [$name]);

        return ($stmt->fetchColumn(0) > 0);
    }

    public function deleteById($id)
    {
        $this->db->delete('users', ['id' => $id]);
    }

    public function deleteByUsername($username)
    {
        $this->db->delete('users', ['freefeed_username' => $username]);
    }

    /**
     * @param int $uid
     * @return array|bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getAccountFields($uid)
    {
        $q = 'SELECT id, freefeed_username, friendfeed_username, email, clio_api_token, backup_me, freefeed_status FROM `users` WHERE id=?';
        $stmt = $this->db->executeQuery($q, [$uid]);

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getAccountFieldsByUsername($username)
    {
        $q = 'SELECT id, freefeed_username, `password`, friendfeed_username, email, clio_api_token, backup_me, freefeed_status FROM `users` WHERE freefeed_username=?';
        $stmt = $this->db->executeQuery($q, [$username]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getAccountFieldsByEmail($email)
    {
        $q = 'SELECT id, freefeed_username, `password`, friendfeed_username, email, clio_api_token, backup_me, freefeed_status FROM `users` WHERE email=?';
        $stmt = $this->db->executeQuery($q, [$email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
