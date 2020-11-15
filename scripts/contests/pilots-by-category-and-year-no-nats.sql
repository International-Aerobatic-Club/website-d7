# MySQL query to retrieve pilot counts by category and year from IACCDB
# DJM, 2019-07-16

SELECT category, YEAR(start) AS yr, COUNT(DISTINCT(members.id)) AS pilots
  FROM contests, flights, categories, pilot_flights, members
  WHERE contest_id = contests.id
    AND category_id = categories.id
    AND pilot_flights.flight_id = flights.id
    AND pilot_flights.pilot_id = members.id
    AND YEAR(start) < YEAR(NOW())
    AND contests.name NOT LIKE "U.S. National Aerobatic Championships%"
  GROUP BY category, yr
  ORDER BY category, yr
