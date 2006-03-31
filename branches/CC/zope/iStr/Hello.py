from Products.Archetypes.public import *

schema =  BaseSchema + Schema(
             StringField('username',
                required=1,
                widget=StringWidget(label='Say hello to'),
             ))

class HelloWorld(BaseContent):
    schema = schema

registerType(HelloWorld)

