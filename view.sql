SELECT
	c.id AS cid,
	c.fullname,
	c.shortname,
	u.id,
	CASE WHEN UltimoAcesso.tc <> 0 THEN TO_CHAR(TO_TIMESTAMP(UltimoAcesso.tc), 'YYYY/mm/dd') ELSE 'Nunca acessado' END AS datacriacao,
	TO_TIMESTAMP(UltimoAcesso.tc),
	UltimoAcesso.tc,
	TO_CHAR(TO_TIMESTAMP(c.startdate), 'YYYY/mm/dd') AS startdate
FROM mdl_user u
INNER JOIN mdl_role_assignments ra ON ra.userid = u.id
INNER JOIN mdl_context ct ON ct.id = ra.contextid
INNER JOIN mdl_course c ON c.id = ct.instanceid
INNER JOIN mdl_role r ON r.id = ra.roleid
INNER JOIN mdl_course_categories cc ON c.category = cc.id 
LEFT JOIN 
(SELECT DISTINCT
	l.userid AS luid,
	l.courseid AS cid,
	MAX(l.timecreated) AS tc
FROM mdl_logstore_standard_log l
WHERE l.eventname = '\core\event\course_viewed'
GROUP BY luid, cid
ORDER BY tc DESC) AS UltimoAcesso ON UltimoAcesso.cid = c.id AND UltimoAcesso.luid = u.id
WHERE u.id = 2 AND c.visible = 1;

