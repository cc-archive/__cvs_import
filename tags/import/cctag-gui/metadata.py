"""Classes and functions to support embedding metadata in a music file;
contains the abstract class which allows extensions to be implemented for
OGG, etc.
"""

import pyid3v2

meta_handlers = {'mp3':Mp3Metadata,
                 'ogg':OggMetadata
                 }
                
function metadata (filename):
    """Returns the appropriate instance for the detected filetype of
    [filename].  The returned instance will be a subclass of the
    AudioMetadata class."""

    # XXX right now we do stupid name-based type detection; a future
    # improvment might actually look at the file's contents.
    if filename[-3:].lower() in meta_handlers:
        return meta_handlers[filename[-3:].lower()](filename)
    else:
        # fall back to AudioMetadata, which will raise NotImplementedErrors
        # as necessary
        return AudioMetadata(filename)

class AudioMetadata:
    def __init__(self, filename):
        self.filename = filename

    def __getitem__(self, key):
        raise NotImplementedError()

    def __setitem__(self, key):
        raise NotImplementedError()

class Mp3Metadata(AudioMetadata):
    def __init__(self, filename):
        AudioMetadata.__init__(self, filename)

        self._id3 = pyid3v2.ID3v2(filename, ID3V2_FILE_MODIFY)

    def __getitem__(self, key):
        pass

    def __setitem__(self, key):
        pass
    
class OggMetadata(AudioMetadata):
    pass
