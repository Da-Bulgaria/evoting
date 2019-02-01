sendVote() {
  this.$store.commit('setLoading', true)

  // get user key pair
  this.keyManager.getKeyPair(this.user.ID).then(userKeyPair => {
	// build vote object
	let vote = JSON.stringify({
	  vote: this.selected.map(c => c.id),
	  nonce: CryptoJS.lib.WordArray.random(16).toString()
	})

	// encrypt vote
	const crypt = new JSEncrypt()
	crypt.setPublicKey(this.dabgPublicKey)
	const encryptedVote = crypt.encrypt(vote)

	// sign encrypted vote
	const sign = new JSEncrypt();
	sign.setPublicKey(userKeyPair.publicKey)
	sign.setPrivateKey(userKeyPair.privateKey)
	const signature = sign.sign(encryptedVote, CryptoJS.SHA256, 'sha256')
	const verified = sign.verify(encryptedVote, signature, CryptoJS.SHA256)

	if (verified) {
	  this.$store.dispatch('vote', { vote: encryptedVote, signature }).then(() => {
		this.voted = true
		this.$store.commit('setLoading', false)
	  }).catch(() => {
		this.err.push('Възникна грешка при гласуването. Моля опитайте отново по-късно.')
		this.$store.commit('setLoading', false)
	  })
	}
  })
}