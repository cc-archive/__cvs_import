import sha

def sha1_hexdigest(in_str):
   sha1 = sha.new(in_str)
   return sha1.hexdigest().upper()

