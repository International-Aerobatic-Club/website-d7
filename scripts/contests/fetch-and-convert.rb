# DJM, 2015-03-04

require 'curb'
require 'json'
require 'csv'

@this_year = Time.now.year

def usage
	STDERR.puts "Usage: ruby #{File.basename(__FILE__)} [year]"
	STDERR.puts "If year is specified, it must be between 2006 and #{@this_year}. Otherwise the current year is assumed."
	exit 1
end

get_year = (ARGV[0] || @this_year).to_i
usage unless ARGV.length <= 1 && get_year >= 2006 && get_year <= @this_year

result = JSON.parse(Curl::Easy.http_get("https://iaccdb.iac.org/contests.json?year=#{get_year}").body_str)

contests = result['contests']

exit 0 if contests.length == 0

puts contests.first.keys.map{ |s| "'#{s}'" }.join(',')
CSV(STDOUT) { |csv| contests.each { |contest| csv << contest.values.map{ |v| "'#{v.to_s.gsub(/'/, "''")}'" } } }

# contests.each { |contest| puts contest['region'] }
