#!/bin/bash

# via https://github.com/rcelha/vagrant-sh-provisioner-scripts/

gen_key(){
    local ID_FILE=$1;

    if [ "${ID_FILE}" == "" ]; then
        echo ID_FILE not defined;
        return 1;
    fi;

    if [ -f "${ID_FILE}" ]; then
        echo "The file ${ID_FILE} already exists.";

		echo "Recording SSH config..."
		record_ssh_config bitbucket.org $ID_FILE ~/.ssh/config;

		echo "Copying key to config/ssh..."
		CURRENT_DIR=`dirname $0`
		mkdir -p $CURRENT_DIR/config/ssh
		cp ~/.ssh/bitbucket.org_id_rsa* $CURRENT_DIR/config/ssh/

        return 1;
    fi;

    ssh-keygen -f ${ID_FILE};
    return $?;
}

retrieve_user(){
    local SERVICE_NAME=$1;
    local SERVICE_USERNAME;
    read -p "$SERVICE_NAME user: " SERVICE_USERNAME;

    echo $SERVICE_USERNAME;
    return 0;
}

retrieve_password(){
    local SERVICE_NAME=$1;
    local SERVICE_PASSWORD;
    read -s -p "password for ${SERVICE_NAME}: " SERVICE_PASSWORD;

    echo $SERVICE_PASSWORD;

    return 0;
}


_send_key(){
    echo "Not implemented";
    exit 1;
}

send_key(){
    local RET;
    local CURL_RET;
    local RETCODE;

    RET=`_send_key ${@}`;
    CURL_RET=$?;

    if [ $CURL_RET -ne 0 ]; then
        echo ;
        echo "Erro on curl command";
        echo $RET;
        return $CURL_RET;
    fi;

    RETCODE=`echo "${RET}" | grep RETCODE | cut -d : -f2`;
    echo $RETCODE;
    if [ $RETCODE -ge 400 ]; then
        echo;
        echo Erro while send the ssh key;
        echo "[API RESPONSE]";
        echo "${RET}"
        return 1;
    fi;
}

record_ssh_config(){
    local SERVICE_NAME=$1;
    local ID_FILE=$2;
    local SSH_CONFIG=$3;

	touch "$SSH_CONFIG"
    CONFIG_IS_SET=`cat ~/.ssh/config | grep "Host $SERVICE_NAME"`;
    if [ ! -z "$CONFIG_IS_SET" ]; then
    	echo "~/.ssh/config is already configured"
    	return 0;
    else
		echo "Configuring ~/.ssh/config"
		echo "
# $SERVICE_NAME CONFIG
Host $SERVICE_NAME
  HostName $SERVICE_NAME
  PreferredAuthentications publickey
  StrictHostKeyChecking no
  IdentityFile ${ID_FILE}" >> ${SSH_CONFIG};
	    chmod 600 ${SSH_CONFIG};
    fi


	CURRENT_DIR=`dirname $0`
	touch $CURRENT_DIR/config/ssh/config
    CONFIG_IS_SET=`cat config/ssh/config | grep "Host $SERVICE_NAME"`;
    if [ ! -z "$CONFIG_IS_SET" ]; then
    	echo "config/ssh/config is already configured"
    	return 0;
    else
		echo "Configuring config/ssh/config"
		mkdir -p $CURRENT_DIR/config/ssh
		echo "
# $SERVICE_NAME CONFIG
Host $SERVICE_NAME
  HostName $SERVICE_NAME
  PreferredAuthentications publickey
  StrictHostKeyChecking no
  IdentityFile /home/vagrant/.ssh/${SERVICE_NAME}_id_rsa" >> $CURRENT_DIR/config/ssh/config;
	fi
    return 0;
}

gen_key_main(){
    local SERVICE_NAME=$1;
    local ID_FILE=${HOME}/.ssh/${SERVICE_NAME}_id_rsa;
    local ID_FILE_PUB=${ID_FILE}.pub;
    local SSH_CONFIG=${HOME}/.ssh/config;
    local USERNAME;
    local PASSWORD;
    local KEY_VALUE;
    local CURL_COMMAND;

    gen_key $ID_FILE $SSH_CONFIG;
    if [ $? != 0 ]; then
        exit 1;
    fi;

    USERNAME=`retrieve_user ${SERVICE_NAME}`;
    PASSWORD=`retrieve_password ${SERVICE_NAME}`;

    send_key $ID_FILE_PUB $USERNAME $PASSWORD;
    if [ $? != 0 ]; then
        exit 1;
    fi;

    record_ssh_config $SERVICE_NAME $ID_FILE $SSH_CONFIG;

	CURRENT_DIR=`dirname $0`
	mkdir -p $CURRENT_DIR/config/ssh
	cp ~/.ssh/bitbucket.org_id_rsa* $CURRENT_DIR/config/ssh/

    echo ;
    echo OK;
    echo ;

    return 0;
}
_send_key(){
    local ID_FILE_PUB=$1;
    local USERNAME=$2;
    local PASSWORD=$3;

    local CURL;
    local KEY_VALUE;

    KEY_VALUE=`cat ${ID_FILE_PUB}`;
    CURL="curl -k -X POST -sL -w \nRETCODE:%{http_code} ";
    CURL="${CURL} --user ${USERNAME}:${PASSWORD}";

    $CURL https://api.bitbucket.org/1.0/users/${USERNAME}/ssh-keys/ -F "key=${KEY_VALUE}" -F "label=vagrant";
    return $?;
}

gen_key_main bitbucket.org;
