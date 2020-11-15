require 'typhoeus'
require 'yaml'
require 'json'

Secrets = YAML.load_file(File.join(File.dirname(__FILE__), 'mailgun_secrets.yml'))

url = "#{Secrets['mailgun_url']}/#{Secrets['domain']}/stats/total?event=delivered&event=failed&duration=12m"

response = Typhoeus.get(url, userpwd: Secrets['api_key'])

headers = response.response_headers.split
unless headers[1] == "200"
  puts "Request failed, response code = #{headers[1]}"
  exit 1
end

body = JSON.parse(response.response_body)
puts JSON.pretty_generate(body)
