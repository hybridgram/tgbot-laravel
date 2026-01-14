<?php

declare(strict_types=1);

namespace HybridGram\Telegram\ChatMember;

enum ChatMemberStatus: string
{
    case CREATOR = 'creator'; // ChatMemberOwner
    case ADMINISTRATOR = 'administrator'; // ChatMemberAdministrator
    case MEMBER = 'member'; // ChatMemberMember
    case RESTRICTED = 'restricted'; // ChatMemberRestricted
    case LEFT = 'left'; // ChatMemberLeft
    case BANNED = 'kicked'; // ChatMemberBanned
}
