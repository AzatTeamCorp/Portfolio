<?php

namespace App\ApiClients\Microsoft;

use App\ApiClients\Microsoft\Providers\ApiProvider;

class MicrosoftApi extends ApiProvider{

    protected $mainAccount;
    protected $calendarId;

    public function __construct($isSso = false){
        $this->mainAccount = config('keys.microsoft.details.main_account');
        $this->calendarId = config('keys.microsoft.details.holiday_calendar_id');
        $this->isSso = $isSso;
    }

    // Users|User|Get | https://learn.microsoft.com/en-us/graph/api/user-get?view=graph-rest-1.0&tabs=http
    public function getMe(): array{
        return $this->v1Api()->get('/me');
    }

    // Users|User|Get | https://learn.microsoft.com/en-us/graph/api/user-get?view=graph-rest-1.0&tabs=http
    public function getUser($email): array{
        return $this->v1Api()->get("/users/$email");
    }

    // Custom function | Get USA holidays for two years
    public function getUsaHolidays(): array{
        $start = \Carbon\Carbon::now()->setYear(date('Y'))->startOfYear()->format('c');
        $end = \Carbon\Carbon::now()->setYear(date('Y')+1)->endOfYear()->format('c');

        return $this->v1Api()->get("/users/$this->mainAccount/calendars/$this->calendarId/calendarView", [
            'select' => 'subject,start,end',
            'StartDateTime' => $start,
            'EndDateTime' => $end,
            'orderby' => 'start/dateTime asc',
            'top' => 100
        ]);
    }

    // Calendars|Calendar view|List calendar view | https://learn.microsoft.com/en-us/graph/api/calendar-list-calendarview?view=graph-rest-1.0&tabs=http
    public function listCalendarView($email, $filter = []): array{
        return $this->v1Api()->get("/users/$email/calendarView", $filter);
    }

    // Sites and lists|Site|List sites https://learn.microsoft.com/en-us/graph/api/site-list?view=graph-rest-1.0&tabs=http
    public function listSites($filter = []): array{
        return $this->v1Api()->get("/sites", $filter);
    }

    // Files|Drive items|Search items | https://learn.microsoft.com/en-us/graph/api/driveitem-search?view=graph-rest-1.0&tabs=http
    public function siteSearchItems($siteId, $filter = []): array{
        return $this->v1Api()->get("/sites/$siteId/drive/root/search", $filter);
    }

    // Calendars|Event|List events | https://learn.microsoft.com/en-us/graph/api/user-list-events?view=graph-rest-1.0&tabs=http
    public function listEvents($email, $filter = []): array{
        if ($email == ''){ $email = $this->mainAccount; }
        return $this->v1Api()->get("/users/$email/calendar/events", $filter);
    }

    // Calendars|Event|Create event | https://learn.microsoft.com/en-us/graph/api/user-post-events?view=graph-rest-1.0&tabs=http
    public function createEvent($email, $event): array{
        if ($email == ''){ $email = $this->mainAccount; }
        return $this->v1Api()->post("/users/$email/calendar/events", $event);
    }

    // Calendars|Event|Get event | https://learn.microsoft.com/en-us/graph/api/event-get?view=graph-rest-1.0&tabs=http
    public function getEvent($email, $eventId): array{
        if ($email == ''){ $email = $this->mainAccount; }
        return $this->v1Api()->get("/users/$email/calendar/events/$eventId");
    }

    // Calendars|Event|Update event | https://learn.microsoft.com/en-us/graph/api/event-update?view=graph-rest-1.0&tabs=http
    public function updateEvent($email, $eventId, $event): array{
        if ($email == ''){ $email = $this->mainAccount; }
        return $this->v1Api()->patch("/users/$email/calendar/events/$eventId", $event);
    }

    // Calendars|Event|Delete event | https://learn.microsoft.com/en-us/graph/api/event-delete?view=graph-rest-1.0&tabs=http
    public function deleteEvent($email, $eventId): array{
        if ($email == ''){ $email = $this->mainAccount; }
        return $this->v1Api()->delete("/users/$email/calendar/events/$eventId");
    }

    // Calendars|Event|List attachments | https://learn.microsoft.com/en-us/graph/api/event-list-attachments?view=graph-rest-1.0&tabs=http
    public function eventListAttachments($email, $eventId): array{
        if ($email == ''){ $email = $this->mainAccount; }
        return $this->v1Api()->get("/users/$email/events/$eventId/attachments");
    }

    // Calendars|Event|Add attachment | https://learn.microsoft.com/en-us/graph/api/event-post-attachments?view=graph-rest-beta&tabs=http
    public function eventAddAttachment($email, $eventId, $attachment): array{
        if ($email == ''){ $email = $this->mainAccount; }
        return $this->betaApi()->post("/users/$email/events/$eventId/attachments", $attachment);
    }

    // Groups|Group|List groups | https://learn.microsoft.com/en-us/graph/api/group-list?view=graph-rest-1.0&tabs=http
    public function listGroups($filter = []): array{
        return $this->v1Api()->get("/groups", $filter);
    }

    // Groups|Group|Create group | https://learn.microsoft.com/en-us/graph/api/group-post-groups?view=graph-rest-1.0&tabs=http
    public function createGroup($group): array{
        return $this->v1Api()->post("/groups", $group);
    }

    // Groups|Group|Delete group | https://learn.microsoft.com/en-us/graph/api/group-delete?view=graph-rest-1.0&tabs=http
    public function deleteGroup($groupId): array{
        return $this->v1Api()->delete("/groups/$groupId");
    }

    // Groups|Group|Add member | https://learn.microsoft.com/en-us/graph/api/group-post-members?view=graph-rest-1.0&tabs=http
    public function addMemberToGroup($groupId, $member): array{
        return $this->v1Api()->post("/groups/$groupId/members/".'$ref', $member);
    }

    // Teamwork and communications|Messaging|Team|Create team from group | https://learn.microsoft.com/en-us/graph/api/team-put-teams?view=graph-rest-1.0&tabs=http
    public function createTeamFromGroup($groupId, $team): array{
        return $this->v1Api()->put("/groups/$groupId/team", $team);
    }

    // Teamwork and communications|Messaging|Team|Add member | https://learn.microsoft.com/en-us/graph/api/team-post-members?view=graph-rest-1.0&tabs=http
    public function addMemberToTeam($teamId, $member): array{
        return $this->v1Api()->post("/teams/$teamId/members", $member);
    }

    // Teamwork and communications|Messaging|Channel|List channels | https://learn.microsoft.com/en-us/graph/api/channel-list?view=graph-rest-1.0&tabs=http
    public function listChannels($teamId): array{
        return $this->v1Api()->get("/teams/$teamId/channels");
    }

    // Teamwork and communications|Messaging|Channel|Create channels | https://learn.microsoft.com/en-us/graph/api/channel-post?view=graph-rest-1.0&tabs=http
    public function createChannel($teamId, $channel): array{
        return $this->v1Api()->post("/teams/$teamId/channels", $channel);
    }

    // Teamwork and communications|Apps|Tab|Tab in channel|Add tab to channel | https://learn.microsoft.com/en-us/graph/api/channel-post-tabs?view=graph-rest-1.0&tabs=http
    public function addTabToChannel($teamId, $channelId, $tab): array{
        return $this->v1Api()->post("/teams/$teamId/channels/$channelId/tabs", $tab);
    }

    // Teamwork and communications|Messaging|Channel|Send message | https://learn.microsoft.com/en-us/graph/api/channel-post-messages?view=graph-rest-1.0&tabs=http
    public function channelSendMessage($teamId, $channelId, $message): array{
        return $this->v1Api()->post("/teams/$teamId/channels/$channelId/messages", $message);
    }

}