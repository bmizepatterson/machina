<?php
/**
 * Session handler base.
 */

namespace session;

defined('MACHINA_INTERNAL') || die();

/**
 * Session handler base.
 */
abstract class handler {
    /**
     * Start the session.
     * @return bool success
     */
    public function start() {
        return session_start();
    }

    /**
     * Init session handler.
     */
    public abstract function init();

    /**
     * Check the backend contains data for this session id.
     *
     * Note: this is intended to be called from manager::session_exists() only.
     *
     * @param string $sid
     * @return bool true if session found.
     */
    public abstract function session_exists($sid);

    /**
     * Kill all active sessions, the core sessions table is
     * purged afterwards.
     */
    public abstract function kill_all_sessions();

    /**
     * Kill one session, the session record is removed afterwards.
     * @param string $sid
     */
    public abstract function kill_session($sid);
}
