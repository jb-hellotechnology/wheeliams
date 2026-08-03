<?php

class PerchMembers_Authenticator_native extends PerchMembers_Authenticator
{
	public function form_login($SubmittedForm)
	{
		$email 		= (isset($SubmittedForm->data['email']) ? $SubmittedForm->data['email'] : false);
		$clear_pwd 	= (isset($SubmittedForm->data['password']) ? $SubmittedForm->data['password'] : false);

		if (!$email || !$clear_pwd) {
			PerchUtil::debug('Email or password not sent.', 'error');
			return false;
		}

		$sql = 'SELECT * FROM '.$this->table.' WHERE memberAuthType=\'native\' AND memberPassword IS NOT NULL AND memberEmail='.$this->db->pdb($email).' AND memberStatus=\'active\' AND (memberExpires IS NULL OR memberExpires>'.$this->db->pdb(date('Y-m-d H:i:s')).') LIMIT 1';
		$result = $this->db->get_row($sql);

		if (PerchUtil::count($result)) {
			if (strlen($clear_pwd) > 72) return false;

            $stored_password = $result['memberPassword'];

            $Hasher = PerchUtil::get_password_hasher();

            if ($Hasher->CheckPassword($clear_pwd, $stored_password)) {
                PerchUtil::debug('Password is ok.', 'auth');

                $user_row = $this->verify_user('native', $result['memberAuthID']);

                return $user_row;

            }else{
                PerchUtil::debug('Password failed to match.', 'auth');
                return false;
            }


		}else{
			PerchUtil::debug('User not found.', 'auth');
		}

		return false;

	}

	/**
	 * Checks existing ('old') password, and if it's valid, encrypts and returns the new password.
	 * @param  [type] $user_row      [description]
	 * @param  [type] $old_clear_pwd [description]
	 * @param  [type] $new_clear_pwd [description]
	 * @return [type]                [description]
	 */
	public function encrypt_new_password($user_row, $old_clear_pwd, $new_clear_pwd)
	{
		 $stored_password = $user_row['memberPassword'];

        $Hasher = PerchUtil::get_password_hasher();

        // Is existing password valid?
        if ($Hasher->CheckPassword($old_clear_pwd, $stored_password) || ($old_clear_pwd=='__auto__' && $stored_password==null)) {

            // Hash and return the new password
            return $Hasher->HashPassword($new_clear_pwd);


        }

        return false;
	}

}
