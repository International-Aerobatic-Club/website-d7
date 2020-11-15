# MySQL query to retrieve contest counts per pilot from IACCDB
# DJM, 2016-12-11

SELECT full_name, count(*) FROM (SELECT DISTINCT CONCAT(given_name, ' ', family_name) AS full_name, contest_id
FROM flights, pilot_flights, members, contests
WHERE flights.id = pilot_flights.flight_id AND
  contest_id = contests.id AND
#  start < '2016-01-01' AND
  pilot_id = members.id) AS XYZ GROUP BY full_name
