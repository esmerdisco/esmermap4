package com.esmermap.app

import android.animation.Animator
import android.animation.AnimatorListenerAdapter
import android.animation.AnimatorSet
import android.animation.ObjectAnimator
import android.content.Intent
import android.os.Bundle
import android.view.animation.AccelerateDecelerateInterpolator
import android.widget.ImageView
import androidx.appcompat.app.AppCompatActivity

class SplashActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_splash)

        val logo = findViewById<ImageView>(R.id.logo)
        val flower = findViewById<ImageView>(R.id.flower)

        // subtle entrance
        logo.alpha = 0f

        val logoFadeIn = ObjectAnimator.ofFloat(logo, "alpha", 0f, 1f).apply {
            duration = 450
        }

        // flower: scale + fade to feel like it grows out of the center
        val startDelay = 320L
        val flowerFadeIn = ObjectAnimator.ofFloat(flower, "alpha", 0f, 1f).apply {
            duration = 260
            this.startDelay = startDelay
        }
        val flowerScaleX = ObjectAnimator.ofFloat(flower, "scaleX", 0.2f, 1.18f, 1.0f).apply {
            duration = 900
            this.startDelay = startDelay
        }
        val flowerScaleY = ObjectAnimator.ofFloat(flower, "scaleY", 0.2f, 1.18f, 1.0f).apply {
            duration = 900
            this.startDelay = startDelay
        }

        val root = window.decorView
        val fadeOut = ObjectAnimator.ofFloat(root, "alpha", 1f, 0f).apply {
            duration = 220
            startDelay = 1450
        }

        val set = AnimatorSet().apply {
            interpolator = AccelerateDecelerateInterpolator()
            playTogether(logoFadeIn, flowerFadeIn, flowerScaleX, flowerScaleY, fadeOut)
            start()
        }

        set.addListener(object : AnimatorListenerAdapter() {
            override fun onAnimationEnd(animation: Animator) {
                startActivity(Intent(this@SplashActivity, MainActivity::class.java))
                finish()
                overridePendingTransition(0, 0)
            }
        })
    }
}
