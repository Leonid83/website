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
     * @param string $friendfeed_username
     * @param string $email
     * @param string $clio_api_token
     * @param bool   $restore_me
     *
     * @return int
     */
    public function register($friendfeed_username, $email, $clio_api_token, $restore_me)
    {
        $this->db->insert('users', [
            'friendfeed_username'   => $friendfeed_username,
            'email'                 => $email,
            'clio_api_token'        => $clio_api_token,
            'account_validated'     => ($clio_api_token !== null),
            'freefeed_status'       => $restore_me ? 'in' : 'undecided'
        ]);

        return $this->db->lastInsertId();
    }

    public function registerRefusal($friendfeed_username, $email, $clio_api_token)
    {
        $this->db->insert('users', [
            'friendfeed_username'   => $friendfeed_username,
            'email'                 => $email,
            'clio_api_token'        => $clio_api_token,
            'account_validated'     => ($clio_api_token !== null),
            'freefeed_status'       => 'out'
        ]);

        return $this->db->lastInsertId();
    }

    public function validateAccount($uid)
    {
        $this->db->update(
            'users',
            ['account_validated' => true],
            ['id' => $uid]
        );
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

    public function changeStatus($uid, $status)
    {
        if (!in_array($status, ['in', 'out', 'undecided'])) {
            throw new \UnexpectedValueException();
        }

        $this->db->update(
            'users',
            ['freefeed_status' => $status],
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

    public function deleteById($id)
    {
        $this->db->delete('users', ['id' => $id]);
    }

    public function deleteByUsername($username)
    {
        $this->db->delete('users', ['friendfeed_username' => $username]);
    }

    /**
     * @param int $uid
     * @return array|bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getAccountFields($uid)
    {
        $q = 'SELECT id, `password`, friendfeed_username, email, clio_api_token, freefeed_status, account_validated, email_validated FROM `users` WHERE id=?';
        $stmt = $this->db->executeQuery($q, [$uid]);

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getAccountFieldsByUsername($username)
    {
        $q = 'SELECT id, `password`, friendfeed_username, email, clio_api_token, freefeed_status, account_validated, email_validated FROM `users` WHERE friendfeed_username=?';
        $stmt = $this->db->executeQuery($q, [$username]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getAccountFieldsByEmail($email)
    {
        $q = 'SELECT id, `password`, friendfeed_username, email, clio_api_token, freefeed_status, account_validated, email_validated FROM `users` WHERE email=?';
        $stmt = $this->db->executeQuery($q, [$email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }


    public function listUnvalidatedAccountsWithEmails()
    {
        $q = 'SELECT * FROM `users` WHERE `account_validated`=0 AND `email_validated`=1 ORDER BY `friendfeed_username`';
        $stmt = $this->db->executeQuery($q);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function listUnconfirmedAccounts()
    {
        $q = 'SELECT * FROM `users` WHERE `email_validated`=0 ORDER BY `friendfeed_username`';
        $stmt = $this->db->executeQuery($q);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function listOptinAccounts()
    {
        $q = 'SELECT * FROM `users` WHERE `account_validated`=1 AND `freefeed_status`="in" ORDER BY `friendfeed_username`';
        $stmt = $this->db->executeQuery($q);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function listOptoutAccounts()
    {
        $q = 'SELECT * FROM `users` WHERE `account_validated`=1 AND `freefeed_status`="out" ORDER BY `friendfeed_username`';
        $stmt = $this->db->executeQuery($q);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function listUndecidedAccounts()
    {
        $q = 'SELECT * FROM `users` WHERE `account_validated`=1 AND `freefeed_status`="undecided" ORDER BY `friendfeed_username`';
        $stmt = $this->db->executeQuery($q);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return array[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function listAccountsWithConfirmedEmails()
    {
        $q = "select `friendfeed_username`, `email` from `users` where `email_validated`=1 and `freefeed_status` != 'out'";
        $stmt = $this->db->executeQuery($q);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
