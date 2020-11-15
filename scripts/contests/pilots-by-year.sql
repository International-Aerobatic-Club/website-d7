# MySQL query to retrieve the number of unique pilots by year from IACCDB
# DJM, 2019-07-16

SELECT  YEAR(start) AS yr, COUNT(DISTINCT(members.id)) AS pilots
  FROM contests, flights, pilot_flights, members
  WHERE contest_id = contests.id
    AND pilot_flights.flight_id = flights.id
    AND pilot_flights.pilot_id = members.id
    AND YEAR(start) < YEAR(NOW())
  GROUP BY yr
  ORDER BY yr
