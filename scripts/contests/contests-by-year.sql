# MySQL query to retrieve contest counts per pilot from IACCDB
# DJM, 2016-12-11

SELECT COUNT(*) AS qty, YEAR(start) AS yr FROM contests GROUP BY yr;
