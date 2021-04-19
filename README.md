# Alexa Radio Skill

[![FOSSA Status](https://app.fossa.com/api/projects/custom%2B2707%2Fgithub.com%2Fdehy%2Falexa-radio-skill.svg?type=shield)](https://app.fossa.com/projects/custom%2B2707%2Fgithub.com%2Fdehy%2Falexa-radio-skill?ref=badge_shield)

Your webradio has now its own Alexa Skill! It supports audio and video streams. You can configure a preroll message with dynamic content from an API.

## Usage

1. Copy the file `config/app_config.yaml.dist` to `config/app_config.yaml` and `.env` to `.env.local`
2. Edit the parameters insides the files to suit your needs. See [Config](#Config) for detailed instructions.

## Config

### .env file

This file contains the technical parameters of the skill. See `.env` for an example.

- `AMAZON_APP_ID`: Set this to the Skill ID from the Skill you created on the [Alexa Developer Console](https://www.developer.amazon.com/alexa/console/ask)
- `CONFIG_FILEPATH`: Path to the other configuration file. Should be `config/app_config.yaml`.
- `FALLBACK_URL`: URL of your webradio in case someone tries to connect to this app from a web browser.
- `APP_DEBUG`: Set to 1 if you want to have more detailed logs when testing your app
- `APP_ENV`: Set to `prod` or `dev`
- `APP_SECRET`: Set to a random string. Used by Symfony for security purposes.

### app_config.yaml file

This file contains the skill main configuration. See `config/app_config.yaml.dist` for an example.

#### `parameters:`

- `audio_stream_uri`: The *https* URL to your webradio audio stream. Set to `null` to disable.
- `video_stream_uri`: The *https* URL to your webradio video stream. Set to `null` to disable.

#### `hooks:`

Hooks are phrases pronounced by the Alexa device at specifics moments. It supports different languages.

- `beforePlayAudio`: Played just before the audio stream launch.
- `beforePlayVideo`: Played just before the video stream launch.
- `onAirAudio`: Played on `OnAirIntent` with audio stream.
- `onAirVideo`: Played on `OnAirIntent` with video stream.

#### `metadatas:`

Those parameters sets the Alexa Device metadatas when playing. They accept static or dynamic values.

#### `endpoints:`

List the API endpoints available as dynamic data sources. Keys (`api.live` in example file) are used as endpoint name. Then, specify url and type with the `source` and `type` keys respectively.

## Dynamic data

This skill supports dynamic data through XML of JSON APIs. First, define the API endpoint in the [`endpoints`](`endpoints:`) section of the `app_config.yaml` file.

### Use in configuration

In the `hooks` or `metadatas` paremeters, you can embed the dynamic values with this pattern: `%[endpoint_name]:[path]%` where `[endpoint_name]` is the endpoint name you set in the `endpoints` parameter, and `[path]` is the path to the property separated by dots.

Example: if your endpoint name is "live_api" and your JSON is `{ "show": { "title": "Show Title", "host": "Jared" }}`, you can embed the show title in the hooks with this string: `"Welcome to %live_api:show.title% with %live_api:show.host%!"`

### Optional sentences

Dynamic values may be missing from API for any reason. In those case, you can construct hooks sentences with optional parts. Simply put braces (`{}`) around those parts. Example `"Welcome to AwesomeRadio! {You're listening to %live_api:song.title% by %live_api:song.artist%!}"`. In this case, if any of the dynamic values is missing, the whole sentence will be skipped.

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## License

[GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0-standalone.html)